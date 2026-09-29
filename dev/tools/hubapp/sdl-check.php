<?php
/**
 * HubApp SDL check — run before every commit that touches a HubApp* schema.
 *
 *   php dev/tools/hubapp/sdl-check.php [--live=<schema.graphql>] [--app=<flutter repo>] [--strict]
 *
 * A schema error in any module's etc/schema.graphqls breaks ALL of /graphql on
 * the live store (the seller app included), and Magento only finds out at the
 * first request after deploy. This script finds out first, with no Magento
 * install: it loads webonyx/graphql-php straight from vendor/ (the version
 * Magento itself uses) and
 *
 *   1. replays Magento's own type splitter (GraphQlReader::parseTypes) on every
 *      HubApp*\/etc/schema.graphqls and fails when it would cut a type
 *      differently from a real parser: a curly brace inside a description, or
 *      a keyword in a comment swallowing the next type;
 *   2. lints naming (hm… root fields, Hm… types, hm_… fields on core types),
 *      @doc coverage, and that every MagentoEgypt resolver / identity class
 *      named in the SDL exists;
 *   3. merges the module files into the LIVE schema the way Magento does
 *      (a re-declared type or interface extends the existing one, interface
 *      fields are copied onto every implementing type), builds it, and runs
 *      full schema validation;
 *   4. validates every smoke document (dev/tools/hubapp/smoke/*.graphql) and
 *      every Flutter operation (<app>/lib/**\/*.graphql) against the result;
 *   5. builds live + HubApp/docs/CONTRACT.graphql, validates ALL smoke
 *      documents against the full contract, and reports drift between each
 *      contract section and the module file it describes.
 *
 * Exit code 1 on any error. "pending" (contract items not yet in a module
 * file) is informational unless --strict.
 */
declare(strict_types=1);

use GraphQL\Error\Error as GraphQlError;
use GraphQL\Language\AST\DefinitionNode;
use GraphQL\Language\AST\DirectiveDefinitionNode;
use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\EnumTypeDefinitionNode;
use GraphQL\Language\AST\EnumTypeExtensionNode;
use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\AST\FragmentSpreadNode;
use GraphQL\Language\AST\InputObjectTypeDefinitionNode;
use GraphQL\Language\AST\InputObjectTypeExtensionNode;
use GraphQL\Language\AST\InterfaceTypeDefinitionNode;
use GraphQL\Language\AST\InterfaceTypeExtensionNode;
use GraphQL\Language\AST\NamedTypeNode;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\NodeList;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Language\AST\ObjectTypeExtensionNode;
use GraphQL\Language\AST\ScalarTypeDefinitionNode;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Language\AST\TypeDefinitionNode;
use GraphQL\Language\AST\TypeExtensionNode;
use GraphQL\Language\AST\UnionTypeDefinitionNode;
use GraphQL\Language\AST\UnionTypeExtensionNode;
use GraphQL\Language\Parser;
use GraphQL\Language\Printer;
use GraphQL\Language\Visitor;
use GraphQL\Type\Schema;
use GraphQL\Utils\BuildSchema;
use GraphQL\Validator\DocumentValidator;

$root = dirname(__DIR__, 3);

spl_autoload_register(static function (string $class) use ($root): void {
    if (strncmp($class, 'GraphQL\\', 8) !== 0) {
        return;
    }
    $file = $root . '/vendor/webonyx/graphql-php/src/' . str_replace('\\', '/', substr($class, 8)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// ---------------------------------------------------------------- options
$opts = getopt('', ['live:', 'app:', 'strict', 'quiet']);
$livePath = (string) ($opts['live'] ?? getenv('HM_LIVE_SDL') ?: 'C:/xampp/htdocs/hubmarket/lib/core/graphql/schema.graphql');
$appPath = (string) ($opts['app'] ?? getenv('HM_APP_REPO') ?: 'C:/xampp/htdocs/hubmarket');
$strict = isset($opts['strict']);
$quiet = isset($opts['quiet']);

$report = new Report($quiet);

// Magento's declarations, with FIELD_DEFINITION allowed for @cache: Magento declares
// it "on QUERY" but reads it from field definitions and never validates locations.
const MAGENTO_DIRECTIVES = <<<'GRAPHQL'
directive @doc(description: String = "") on QUERY | MUTATION | FIELD | FRAGMENT_DEFINITION | FRAGMENT_SPREAD | INLINE_FRAGMENT | SCHEMA | SCALAR | OBJECT | FIELD_DEFINITION | ARGUMENT_DEFINITION | INTERFACE | UNION | ENUM | ENUM_VALUE | INPUT_OBJECT | INPUT_FIELD_DEFINITION
directive @resolver(class: String = "") on QUERY | MUTATION | FIELD | FRAGMENT_DEFINITION | FRAGMENT_SPREAD | INLINE_FRAGMENT | SCHEMA | SCALAR | OBJECT | FIELD_DEFINITION | ARGUMENT_DEFINITION | ENUM | ENUM_VALUE | INPUT_OBJECT | INPUT_FIELD_DEFINITION
directive @typeResolver(class: String = "") on UNION | INTERFACE | OBJECT
directive @cache(cacheIdentity: String = "", cacheable: Boolean = true) on QUERY | FIELD_DEFINITION
directive @implementation(class: String = "") on SCALAR | OBJECT
GRAPHQL;

// Dart reserved words: a field with one of these names breaks graphql_codegen.
const DART_RESERVED = [
    'abstract', 'as', 'assert', 'async', 'await', 'break', 'case', 'catch', 'class', 'const', 'continue',
    'covariant', 'default', 'deferred', 'do', 'dynamic', 'else', 'enum', 'export', 'extends', 'extension',
    'external', 'factory', 'false', 'final', 'finally', 'for', 'function', 'get', 'hide', 'if', 'implements',
    'import', 'in', 'interface', 'is', 'late', 'library', 'mixin', 'new', 'null', 'on', 'operator', 'part',
    'required', 'rethrow', 'return', 'set', 'show', 'static', 'super', 'switch', 'sync', 'this', 'throw',
    'true', 'try', 'typedef', 'var', 'void', 'while', 'with', 'yield',
];

// ---------------------------------------------------------------- live schema
if (!is_file($livePath)) {
    $report->error("live schema not found: {$livePath} (use --live=… or HM_LIVE_SDL)");
    exit($report->finish($strict));
}
try {
    $liveDoc = Parser::parse((string) file_get_contents($livePath), ['noLocation' => true]);
} catch (\Throwable $e) {
    $report->error('live schema does not parse: ' . $e->getMessage());
    exit($report->finish($strict));
}
$liveTypes = [];
foreach ($liveDoc->definitions as $def) {
    if ($def instanceof TypeDefinitionNode) {
        $liveTypes[$def->getName()->value] = $def;
    }
}
$report->head('live schema: ' . $livePath . ' (' . count($liveTypes) . ' types)');

// ---------------------------------------------------------------- module files
$moduleFiles = glob($root . '/app/code/MagentoEgypt/HubApp*/etc/schema.graphqls') ?: [];
usort($moduleFiles, static function (string $a, string $b): int {
    // HubApp (core) first, the satellites after it, as Magento's module sequence loads them.
    $ma = moduleOf($a);
    $mb = moduleOf($b);
    if ($ma === 'HubApp') {
        return -1;
    }
    if ($mb === 'HubApp') {
        return 1;
    }

    return strcmp($ma, $mb);
});

$modules = [];   // module => DocumentNode
$knownTypes = $liveTypes;   // live + everything declared by the modules loaded so far
foreach ($moduleFiles as $file) {
    $module = moduleOf($file);
    $source = (string) file_get_contents($file);
    $report->head("module {$module}: " . rel($file, $root));
    $doc = checkSource($report, $module, $source, $liveTypes, $knownTypes, $root, true);
    if ($doc !== null) {
        $modules[$module] = $doc;
        $knownTypes += typeMap($doc);
    }
}
if (!$moduleFiles) {
    $report->error('no app/code/MagentoEgypt/HubApp*/etc/schema.graphqls found under ' . $root);
}

// ---------------------------------------------------------------- merged schema
$report->head('merged schema: live + ' . (implode(', ', array_keys($modules)) ?: 'nothing'));
$schema = buildMerged($report, $liveDoc, $modules);

// ---------------------------------------------------------------- documents
$smokeDir = __DIR__ . '/smoke';
$smokeDocs = [];
foreach (glob($smokeDir . '/*.graphql') ?: [] as $file) {
    $smokeDocs[$file] = (string) file_get_contents($file);
}
$appDocs = [];
if (is_dir($appPath . '/lib')) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($appPath . '/lib', FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        /** @var SplFileInfo $f */
        $path = str_replace('\\', '/', $f->getPathname());
        if ($f->isFile() && str_ends_with($path, '.graphql') && !str_ends_with($path, '/core/graphql/schema.graphql')) {
            $appDocs[$path] = (string) file_get_contents($path);
        }
    }
    ksort($appDocs);
} else {
    $report->warn("Flutter repo not found at {$appPath}; app operations not checked (use --app=…)");
}

if ($schema !== null) {
    $report->head('documents against the merged schema');
    $present = array_keys($modules);
    foreach ($smokeDocs as $file => $source) {
        $missing = array_diff(requiredModules($source), $present);
        if ($missing) {
            $report->pending(rel($file, $root) . ': needs ' . implode(', ', $missing));
            continue;
        }
        validateDocument($report, $schema, rel($file, $root), $source, $smokeDocs + $appDocs);
    }
    foreach ($appDocs as $file => $source) {
        validateDocument($report, $schema, 'app:' . rel($file, $appPath), $source, $appDocs);
    }
}

// ---------------------------------------------------------------- contract
$contractFile = $root . '/app/code/MagentoEgypt/HubApp/docs/CONTRACT.graphql';
if (!is_file($contractFile)) {
    $report->error('contract not found: ' . rel($contractFile, $root));
} else {
    $report->head('contract: ' . rel($contractFile, $root));
    $sections = contractSections((string) file_get_contents($contractFile));
    if (!$sections) {
        $report->error('contract has no "# ===== <Module>/etc/schema.graphqls" sections');
    }
    $contractDocs = [];
    $knownTypes = $liveTypes;
    foreach ($sections as $module => $source) {
        //  Each section is what that module's schema.graphqls must contain, so it must
        //  also survive Magento's type splitter. Classes are not checked here: a
        //  satellite's resolvers exist only once its owner has written them.
        $doc = checkSource($report, "contract[{$module}]", $source, $liveTypes, $knownTypes, $root, false);
        if ($doc !== null) {
            $contractDocs[$module] = $doc;
            $knownTypes += typeMap($doc);
        }
    }
    $contractSchema = $contractDocs ? buildMerged($report, $liveDoc, $contractDocs, 'contract') : null;
    if ($contractSchema !== null) {
        foreach ($smokeDocs as $file => $source) {
            validateDocument($report, $contractSchema, 'contract:' . rel($file, $root), $source, $smokeDocs);
        }
    }
    foreach ($contractDocs as $module => $contractDoc) {
        if (!isset($modules[$module])) {
            $report->pending("{$module}: module schema not written yet (contract section has " . count($contractDoc->definitions) . ' definitions)');
            continue;
        }
        compareToContract($report, $module, $modules[$module], $contractDoc);
    }
    foreach (array_keys($modules) as $module) {
        if (!isset($contractDocs[$module])) {
            $report->error("{$module}: module schema has no section in the contract");
        }
    }
}

exit($report->finish($strict));

// =============================================================================

/**
 * Reader, lint and parse checks on one schema.graphqls text. Returns the parsed document.
 *
 * @param array<string, TypeDefinitionNode> $liveTypes core types (fields added to them need hm_)
 * @param array<string, TypeDefinitionNode> $knownTypes live + types of HubApp modules loaded before
 */
function checkSource(
    Report $report,
    string $label,
    string $source,
    array $liveTypes,
    array $knownTypes,
    string $root,
    bool $checkClasses
): ?DocumentNode {
    try {
        $doc = Parser::parse($source, ['noLocation' => true]);
    } catch (\Throwable $e) {
        $report->error("{$label}: does not parse: " . $e->getMessage());

        return null;
    }

    // 1. Magento's type splitter.
    $expected = [];
    foreach ($doc->definitions as $def) {
        if ($def instanceof TypeExtensionNode) {
            $report->error("{$label}: 'extend' is not how Magento merges types; redeclare the type instead (" . $def->getName()->value . ')');
            continue;
        }
        if ($def instanceof TypeDefinitionNode) {
            $expected[] = $def->getName()->value;
        } elseif ($def instanceof DirectiveDefinitionNode) {
            $report->warn("{$label}: directive definition @" . $def->name->value . ' (Magento reads these only from Magento_GraphQl)');
        }
    }
    $split = magentoParseTypes($source);
    $seen = array_column($split, 'name');
    if ($seen !== $expected) {
        $report->error(sprintf(
            "%s: Magento's type splitter would read [%s] where the parser reads [%s] — a curly brace inside a description or a keyword in a comment",
            $label,
            implode(', ', $seen),
            implode(', ', $expected)
        ));
    }
    foreach ($split as $chunk) {
        try {
            $one = Parser::parse($chunk['text'], ['noLocation' => true]);
            $names = [];
            foreach ($one->definitions as $d) {
                if ($d instanceof TypeDefinitionNode) {
                    $names[] = $d->getName()->value;
                }
            }
            if ($names !== [$chunk['name']]) {
                $report->error("{$label}: Magento's chunk for {$chunk['name']} holds [" . implode(', ', $names) . ']');
            }
        } catch (\Throwable $e) {
            $report->error("{$label}: Magento's chunk for {$chunk['name']} does not parse: " . firstLine($e->getMessage()));
        }
    }
    if ($seen === $expected) {
        $report->ok("{$label}: Magento type splitter agrees (" . count($expected) . ' types)');
    }

    // Braces inside any string are what breaks the splitter; name them precisely.
    Visitor::visit($doc, [
        NodeKind::STRING => static function (StringValueNode $node) use ($report, $label): void {
            if (strpbrk($node->value, '{}') !== false) {
                $report->error("{$label}: curly brace in a string: \"" . $node->value . '"');
            }
        },
    ]);

    // 2. Naming, @doc coverage, classes.
    $newTypes = 0;
    foreach ($doc->definitions as $def) {
        if (!$def instanceof TypeDefinitionNode) {
            continue;
        }
        $name = $def->getName()->value;
        $isRoot = in_array($name, ['Query', 'Mutation'], true);
        $isCore = isset($liveTypes[$name]);
        $isExisting = isset($knownTypes[$name]);

        if (!$isRoot && !$isExisting && !str_starts_with($name, 'Hm')) {
            $report->error("{$label}: new type {$name} must be named Hm…");
        }
        if (!$isRoot && !$isExisting) {
            $newTypes++;
            if (!hasDoc($def->directives)) {
                $report->warn("{$label}: type {$name} has no @doc");
            }
        }

        foreach (fieldsOf($def) as $field) {
            $fname = $field->name->value;
            if ($isRoot && !str_starts_with($fname, 'hm')) {
                $report->error("{$label}: root field {$name}.{$fname} must be named hm…");
            } elseif (!$isRoot && $isCore && !str_starts_with($fname, 'hm_')) {
                $report->error("{$label}: field {$fname} added to core type {$name} must be named hm_…");
            }
            if (in_array($fname, DART_RESERVED, true)) {
                $report->error("{$label}: {$name}.{$fname} is a Dart reserved word (breaks the app's codegen)");
            }
            if (!hasDoc($field->directives)) {
                $report->warn("{$label}: {$name}.{$fname} has no @doc");
            }
            $resolver = directiveArg($field->directives, 'resolver', 'class');
            $cacheIdentity = directiveArg($field->directives, 'cache', 'cacheIdentity');
            if ($isRoot && $resolver === null) {
                $report->error("{$label}: root field {$name}.{$fname} has no @resolver");
            }
            if ($cacheIdentity !== null && $resolver === null) {
                $report->error("{$label}: {$name}.{$fname} has @cache but no @resolver");
            }
            if ($checkClasses) {
                foreach (array_filter([$resolver, $cacheIdentity]) as $class) {
                    checkClass($report, $label, "{$name}.{$fname}", $class, $root);
                }
            }
            if ($field instanceof FieldDefinitionNode) {
                foreach ($field->arguments as $arg) {
                    if (!hasDoc($arg->directives)) {
                        $report->warn("{$label}: argument {$name}.{$fname}({$arg->name->value}) has no @doc");
                    }
                }
            }
        }
        if ($checkClasses) {
            $typeResolver = directiveArg($def->directives, 'typeResolver', 'class');
            if ($typeResolver !== null) {
                checkClass($report, $label, $name, $typeResolver, $root);
            }
        }
    }
    $report->ok("{$label}: naming and docs checked ({$newTypes} new types)");

    return $doc;
}

/**
 * @return array<string, TypeDefinitionNode>
 */
function typeMap(DocumentNode $doc): array
{
    $out = [];
    foreach ($doc->definitions as $def) {
        if ($def instanceof TypeDefinitionNode) {
            $out[$def->getName()->value] = $def;
        }
    }

    return $out;
}

/**
 * Magento\Framework\GraphQlSchemaStitching\GraphQlReader::parseTypes(), verbatim regex.
 *
 * @return array<int, array{name: string, text: string}>
 */
function magentoParseTypes(string $source): array
{
    $typeKindsPattern = '(type|interface|union|enum|input|scalar)';
    $typeNamePattern = '([_A-Za-z][_0-9A-Za-z]+)';
    $typeDefinitionPattern = '([^\{\}]*)(\{[^\}]*\})';
    $spacePattern = '[\s\t\n\r]+';
    preg_match_all(
        "/{$typeKindsPattern}{$spacePattern}{$typeNamePattern}{$spacePattern}{$typeDefinitionPattern}/i",
        $source,
        $matches
    );
    $out = [];
    foreach ($matches[0] as $i => $text) {
        $out[] = ['name' => $matches[2][$i], 'text' => $text];
    }

    return $out;
}

/**
 * Live schema + module documents, merged the Magento way, built and validated.
 *
 * @param array<string, DocumentNode> $modules in load order
 */
function buildMerged(Report $report, DocumentNode $liveDoc, array $modules, string $label = 'merged'): ?Schema
{
    $definitions = [];
    foreach (Parser::parse(MAGENTO_DIRECTIVES, ['noLocation' => true])->definitions as $d) {
        $definitions[] = $d;
    }

    /** @var array<string, TypeDefinitionNode> $types name => definition */
    $types = [];
    /** @var array<string, array<string, true>> $fieldNames name => field names so far */
    $fieldNames = [];
    foreach ($liveDoc->definitions as $d) {
        $definitions[] = $d;
        if ($d instanceof TypeDefinitionNode) {
            $types[$d->getName()->value] = $d;
            $fieldNames[$d->getName()->value] = namesOf($d);
        }
    }

    $extensions = [];
    $interfaceAdds = [];   // interface => FieldDefinitionNode[]
    foreach ($modules as $module => $doc) {
        foreach ($doc->definitions as $d) {
            if (!$d instanceof TypeDefinitionNode) {
                continue;
            }
            $name = $d->getName()->value;
            if (!isset($types[$name])) {
                $definitions[] = $d;
                $types[$name] = $d;
                $fieldNames[$name] = namesOf($d);
                continue;
            }
            if ($types[$name]->kind !== $d->kind) {
                $report->error("{$label}: {$module} redeclares {$name} as " . $d->kind . ', it is ' . $types[$name]->kind);
                continue;
            }
            foreach (namesOf($d) as $field => $true) {
                if (isset($fieldNames[$name][$field])) {
                    //  Magento would silently replace the existing definition. Never intended.
                    $report->error("{$label}: {$module} redefines existing {$name}.{$field}");
                }
                $fieldNames[$name][$field] = true;
            }
            $ext = toExtension($d);
            if ($ext !== null) {
                $extensions[] = $ext;
            }
            if ($d instanceof InterfaceTypeDefinitionNode) {
                foreach ($d->fields as $f) {
                    $interfaceAdds[$name][] = $f;
                }
            }
        }
    }

    //  GraphQlReader::copyInterfaceFieldsToConcreteTypes(): implementers get the new fields.
    foreach ($interfaceAdds as $interface => $fields) {
        foreach ($types as $typeName => $type) {
            if (!$type instanceof ObjectTypeDefinitionNode || !implementsInterface($type, $interface)) {
                continue;
            }
            $add = [];
            foreach ($fields as $f) {
                if (!isset($fieldNames[$typeName][$f->name->value])) {
                    $add[] = $f;
                    $fieldNames[$typeName][$f->name->value] = true;
                }
            }
            if ($add) {
                $extensions[] = new ObjectTypeExtensionNode([
                    'name' => $type->name,
                    'interfaces' => new NodeList([]),
                    'directives' => new NodeList([]),
                    'fields' => new NodeList($add),
                ]);
            }
        }
    }

    $document = new DocumentNode(['definitions' => new NodeList(array_merge($definitions, $extensions))]);
    try {
        $schema = BuildSchema::buildAST($document);
        $schema->assertValid();
    } catch (\Throwable $e) {
        $report->error("{$label}: schema invalid: " . $e->getMessage());

        return null;
    }
    $report->ok("{$label}: schema builds and validates (" . count($schema->getTypeMap()) . ' types, ' . count($extensions) . ' extensions)');

    return $schema;
}

/**
 * @param array<string, string> $library other documents, for fragments defined elsewhere
 */
function validateDocument(Report $report, Schema $schema, string $label, string $source, array $library): void
{
    try {
        $doc = Parser::parse($source);
    } catch (\Throwable $e) {
        $report->error("{$label}: does not parse: " . firstLine($e->getMessage()));

        return;
    }
    $doc = withFragments($doc, $library);
    $errors = DocumentValidator::validate($schema, $doc);
    if (!$errors) {
        $report->ok("{$label}");

        return;
    }
    foreach ($errors as $error) {
        /** @var GraphQlError $error */
        $where = '';
        $locations = $error->getLocations();
        if ($locations) {
            $where = ' (line ' . $locations[0]->line . ')';
        }
        $report->error("{$label}: " . $error->getMessage() . $where);
    }
}

/**
 * Append fragment definitions the document uses but defines elsewhere.
 *
 * @param array<string, string> $library
 */
function withFragments(DocumentNode $doc, array $library): DocumentNode
{
    static $fragments = null;
    static $libraryKey = null;
    $key = md5(implode("\0", array_keys($library)));
    if ($fragments === null || $libraryKey !== $key) {
        $fragments = [];
        foreach ($library as $src) {
            try {
                foreach (Parser::parse($src)->definitions as $d) {
                    if ($d instanceof FragmentDefinitionNode) {
                        $fragments[$d->name->value] ??= $d;
                    }
                }
            } catch (\Throwable $e) {
                continue;
            }
        }
        $libraryKey = $key;
    }

    $defined = [];
    foreach ($doc->definitions as $d) {
        if ($d instanceof FragmentDefinitionNode) {
            $defined[$d->name->value] = true;
        }
    }
    $added = [];
    $queue = [$doc];
    while ($queue) {
        $node = array_shift($queue);
        $spreads = [];
        Visitor::visit($node, [
            NodeKind::FRAGMENT_SPREAD => static function (FragmentSpreadNode $s) use (&$spreads): void {
                $spreads[] = $s->name->value;
            },
        ]);
        foreach ($spreads as $name) {
            if (!isset($defined[$name]) && isset($fragments[$name])) {
                $defined[$name] = true;
                $added[] = $fragments[$name];
                $queue[] = $fragments[$name];
            }
        }
    }
    if (!$added) {
        return $doc;
    }
    $all = [];
    foreach ($doc->definitions as $d) {
        $all[] = $d;
    }

    return new DocumentNode(['definitions' => new NodeList(array_merge($all, $added))]);
}

/**
 * @return array<string, string> module => section source
 */
function contractSections(string $contract): array
{
    $parts = preg_split('/^# =+ (HubApp\w*)\/etc\/schema\.graphqls[^\n]*$/m', $contract, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
    $out = [];
    for ($i = 1; $i + 1 < count($parts); $i += 2) {
        $out[$parts[$i]] = $parts[$i + 1];
    }

    return $out;
}

/**
 * Drift between a module's schema.graphqls and its contract section, by printed definition.
 */
function compareToContract(Report $report, string $module, DocumentNode $actual, DocumentNode $contract): void
{
    $a = definitionMap($actual);
    $c = definitionMap($contract);
    $drift = 0;
    $pending = 0;
    foreach ($a as $name => $def) {
        if (!isset($c[$name])) {
            $report->error("{$module}: {$name} is in the module but not in the contract");
            $drift++;
            continue;
        }
        if ($def['head'] !== $c[$name]['head']) {
            $report->error("{$module}: {$name} header differs from the contract\n      module:   {$def['head']}\n      contract: {$c[$name]['head']}");
            $drift++;
        }
        foreach ($def['members'] as $member => $printed) {
            if (!isset($c[$name]['members'][$member])) {
                $report->error("{$module}: {$name}.{$member} is in the module but not in the contract");
                $drift++;
            } elseif ($printed !== $c[$name]['members'][$member]) {
                $report->error("{$module}: {$name}.{$member} differs from the contract\n      module:   {$printed}\n      contract: {$c[$name]['members'][$member]}");
                $drift++;
            }
        }
        foreach ($c[$name]['members'] as $member => $printed) {
            if (!isset($def['members'][$member])) {
                $report->pending("{$module}: {$name}.{$member} not implemented yet");
                $pending++;
            }
        }
    }
    foreach ($c as $name => $def) {
        if (!isset($a[$name])) {
            $report->pending("{$module}: {$name} not implemented yet");
            $pending++;
        }
    }
    if ($drift === 0) {
        $report->ok("{$module}: matches its contract section" . ($pending ? " ({$pending} pending)" : ''));
    }
}

/**
 * @return array<string, array{head: string, members: array<string, string>}>
 */
function definitionMap(DocumentNode $doc): array
{
    $out = [];
    foreach ($doc->definitions as $def) {
        if (!$def instanceof TypeDefinitionNode) {
            continue;
        }
        $members = [];
        foreach (fieldsOf($def) as $member) {
            $members[$member->name->value] = oneLine(Printer::doPrint($member));
        }
        if ($def instanceof EnumTypeDefinitionNode) {
            foreach ($def->values as $value) {
                $members[$value->name->value] = oneLine(Printer::doPrint($value));
            }
        }
        $head = $def->kind . ' ' . $def->getName()->value;
        foreach ($def->directives as $directive) {
            $head .= ' ' . oneLine(Printer::doPrint($directive));
        }
        $out[$def->getName()->value] = ['head' => $head, 'members' => $members];
    }

    return $out;
}

/**
 * Fields (object, interface) or input fields (input object) of a definition.
 *
 * @return array<int, Node>
 */
function fieldsOf(Node $def): array
{
    $out = [];
    if ($def instanceof ObjectTypeDefinitionNode || $def instanceof InterfaceTypeDefinitionNode
        || $def instanceof InputObjectTypeDefinitionNode) {
        foreach ($def->fields as $f) {
            $out[] = $f;
        }
    }

    return $out;
}

/**
 * @return array<string, true>
 */
function namesOf(Node $def): array
{
    $out = [];
    foreach (fieldsOf($def) as $f) {
        $out[$f->name->value] = true;
    }
    if ($def instanceof EnumTypeDefinitionNode) {
        foreach ($def->values as $v) {
            $out[$v->name->value] = true;
        }
    }

    return $out;
}

function toExtension(TypeDefinitionNode $def): ?Node
{
    //  Type-level directives stay on the original: @doc is not repeatable.
    $none = new NodeList([]);
    if ($def instanceof ObjectTypeDefinitionNode) {
        return new ObjectTypeExtensionNode(['name' => $def->name, 'interfaces' => $def->interfaces, 'directives' => $none, 'fields' => $def->fields]);
    }
    if ($def instanceof InterfaceTypeDefinitionNode) {
        return new InterfaceTypeExtensionNode(['name' => $def->name, 'interfaces' => $def->interfaces, 'directives' => $none, 'fields' => $def->fields]);
    }
    if ($def instanceof InputObjectTypeDefinitionNode) {
        return new InputObjectTypeExtensionNode(['name' => $def->name, 'directives' => $none, 'fields' => $def->fields]);
    }
    if ($def instanceof EnumTypeDefinitionNode) {
        return new EnumTypeExtensionNode(['name' => $def->name, 'directives' => $none, 'values' => $def->values]);
    }
    if ($def instanceof UnionTypeDefinitionNode) {
        return new UnionTypeExtensionNode(['name' => $def->name, 'directives' => $none, 'types' => $def->types]);
    }
    if ($def instanceof ScalarTypeDefinitionNode) {
        return null;
    }

    return null;
}

function implementsInterface(ObjectTypeDefinitionNode $type, string $interface): bool
{
    foreach ($type->interfaces as $i) {
        /** @var NamedTypeNode $i */
        if ($i->name->value === $interface) {
            return true;
        }
    }

    return false;
}

/**
 * @param NodeList<DirectiveNode> $directives
 */
function hasDoc(NodeList $directives): bool
{
    $doc = directiveArg($directives, 'doc', 'description');

    return $doc !== null && trim($doc) !== '';
}

/**
 * @param NodeList<DirectiveNode> $directives
 */
function directiveArg(NodeList $directives, string $directive, string $argument): ?string
{
    foreach ($directives as $d) {
        if ($d->name->value !== $directive) {
            continue;
        }
        foreach ($d->arguments as $arg) {
            if ($arg->name->value === $argument && $arg->value instanceof StringValueNode) {
                return $arg->value->value;
            }
        }
    }

    return null;
}

function checkClass(Report $report, string $label, string $where, string $class, string $root): void
{
    $class = ltrim($class, '\\');
    if (!str_starts_with($class, 'MagentoEgypt\\')) {
        return;   // core classes live in vendor/, outside this sparse checkout
    }
    $file = $root . '/app/code/' . str_replace('\\', '/', $class) . '.php';
    if (!is_file($file)) {
        $report->error("{$label}: {$where} names {$class}, which does not exist");
    }
}

/**
 * Modules a smoke document needs, from a "# requires: HubAppVendors, HubAppBundle" line.
 *
 * @return string[]
 */
function requiredModules(string $source): array
{
    if (!preg_match('/^#\s*requires:\s*(.+)$/mi', $source, $m)) {
        return ['HubApp'];
    }

    return array_values(array_filter(array_map('trim', explode(',', $m[1]))));
}

function moduleOf(string $file): string
{
    return preg_match('~MagentoEgypt[/\\\\](HubApp\w*)[/\\\\]~', $file, $m) ? $m[1] : basename(dirname($file, 2));
}

function rel(string $path, string $base): string
{
    $path = str_replace('\\', '/', $path);
    $base = rtrim(str_replace('\\', '/', $base), '/') . '/';

    return str_starts_with($path, $base) ? substr($path, strlen($base)) : $path;
}

function oneLine(string $s): string
{
    return trim((string) preg_replace('/\s+/', ' ', $s));
}

function firstLine(string $s): string
{
    return strtok($s, "\n") ?: $s;
}

final class Report
{
    private int $errors = 0;
    private int $warnings = 0;
    private int $pending = 0;

    public function __construct(private readonly bool $quiet)
    {
    }

    public function head(string $s): void
    {
        echo "\n== {$s}\n";
    }

    public function ok(string $s): void
    {
        if (!$this->quiet) {
            echo "  ok      {$s}\n";
        }
    }

    public function warn(string $s): void
    {
        $this->warnings++;
        echo "  WARN    {$s}\n";
    }

    public function pending(string $s): void
    {
        $this->pending++;
        if (!$this->quiet) {
            echo "  pending {$s}\n";
        }
    }

    public function error(string $s): void
    {
        $this->errors++;
        echo "  ERROR   {$s}\n";
    }

    public function finish(bool $strict): int
    {
        $failed = $this->errors > 0 || ($strict && $this->pending > 0);
        echo sprintf(
            "\n%s: %d error(s), %d warning(s), %d pending%s\n",
            $failed ? 'FAIL' : 'PASS',
            $this->errors,
            $this->warnings,
            $this->pending,
            $strict ? ' (strict)' : ''
        );

        return $failed ? 1 : 0;
    }
}
