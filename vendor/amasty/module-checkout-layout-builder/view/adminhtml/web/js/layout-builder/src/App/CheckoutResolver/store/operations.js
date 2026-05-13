import cloneDeep from 'lodash/cloneDeep';
import { selectors, defaultConfig } from '../constants';
import { ActionCreators, ActionDispatchers } from './actions';
import { ActionCreators as BuilderActionCreators } from '@layoutBuilder/store/actions';

const addDesignHandler = (dispatch) => {
    const designSelect = document.getElementById(selectors.CHECKOUT_DESIGN_INPUT_ID);
    const classicLayoutSelect = document.getElementById(selectors.CHECKOUT_CLASSIC_DESIGN_LAYOUT_INPUT_ID);
    const modernLayoutSelect = document.getElementById(selectors.CHECKOUT_MODERN_DESIGN_LAYOUT_INPUT_ID);
    let selectedLayout = defaultConfig.selectedLayout,
        designLabel = defaultConfig.defaultLayout;

    designSelect?.addEventListener('change', event => {
        const designValue = +event.target.value;

        designLabel = designValue === 1 ? 'modern' : 'classic';
        selectedLayout = designValue === 1
            ? modernLayoutSelect.value
            : classicLayoutSelect.value || selectedLayout;

        dispatchDesignHandlerActions(dispatch, designLabel, selectedLayout);
    });
};

const dispatchDesignHandlerActions = (dispatch, designLabel, selectedLayout) => {
    dispatch(ActionCreators.changeCheckoutDesignAction(designLabel));
    dispatch(ActionCreators.changeCheckoutLayoutAction(selectedLayout));
    dispatch(ActionDispatchers.changeCheckoutLayoutColumnsWidthDispatcher());
    dispatch(ActionDispatchers.applyCheckoutConfigToBuilder(designLabel, selectedLayout));
}

const addClassicLayoutHandler = (dispatch) => {
    const classicLayoutSelect = document.getElementById(selectors.CHECKOUT_CLASSIC_DESIGN_LAYOUT_INPUT_ID);

    classicLayoutSelect?.addEventListener('change', event => {
        const layoutValue = event.target.value,
            designValue = +document.getElementById(selectors.CHECKOUT_DESIGN_INPUT_ID).value;

        if (designValue === 0) {
            dispatch(ActionCreators.changeCheckoutLayoutAction(layoutValue));
            dispatch(ActionDispatchers.changeCheckoutLayoutColumnsWidthDispatcher());
            dispatch(ActionDispatchers.applyCheckoutConfigToBuilder('classic', layoutValue));
        }
    });
};

const addModernLayoutHandler = (dispatch) => {
    const modernLayoutSelect = document.getElementById(selectors.CHECKOUT_MODERN_DESIGN_LAYOUT_INPUT_ID);

    modernLayoutSelect?.addEventListener('change', event => {
        const layoutValue = event.target.value,
            designValue = +document.getElementById(selectors.CHECKOUT_DESIGN_INPUT_ID).value;

        if (designValue === 1) {
            dispatch(ActionCreators.changeCheckoutLayoutAction(layoutValue));
            dispatch(ActionDispatchers.changeCheckoutLayoutColumnsWidthDispatcher());
            dispatch(ActionDispatchers.applyCheckoutConfigToBuilder('modern', layoutValue));
        }
    });
};

const addInheritCheckboxHandler = (dispatch) => {
    const inheritCheckbox = document.getElementById(selectors.CHECKOUT_LAYOUT_CONFIG_FIELD_INHERIT_CHECKBOX_ID);

    inheritCheckbox?.addEventListener('change', event => {
        dispatch(BuilderActionCreators.toggleBuilderStatus(!event.target.checked));
    });
};

const addDesignFieldsetDisplayStatusHandler = (dispatch, builderContainerId) => {
    const designSectionStatusObserver = new MutationObserver(mutations => {
        mutations.forEach(mutation => {
            if (mutation.target.style.display !== 'none') {
                dispatch(BuilderActionCreators.changeBuilderWidthAction(document.getElementById(builderContainerId).offsetWidth));
            }
        });
    });

    const checkoutConfigDesignLayoutSection = document.getElementById(selectors.CHECKOUT_CONFIG_DESIGN_SECTION_ID);

    if (checkoutConfigDesignLayoutSection) {
        designSectionStatusObserver.observe(checkoutConfigDesignLayoutSection, {
            attributeFilter: [ 'style' ]
        });
    }
};

const addSeparateBillingHandler = (dispatch) => {
    const separateBillingAddressSelect = document.getElementById(selectors.CHECKOUT_SEPARATE_BILLING_ADDRESS_INPUT_ID);

    separateBillingAddressSelect?.addEventListener('change', (event) => {
        const selectValue = +event.target.value;

        dispatch(ActionDispatchers.changeSeparateBillingState(!!selectValue));
    });
};

const addPrimaryAddressHandler = (dispatch) => {
    const primaryAddressSelect = document.getElementById(selectors.CHECKOUT_SELECT_PRIMARY_ADDRESS_INPUT_ID);

    primaryAddressSelect?.addEventListener('change', (event) => {
        dispatch(ActionDispatchers.changeCheckoutPrimaryAddressDispatcher(event.target.value));
    });
};

const addCheckoutSettingsHandlers = (dispatch, builderContainerId) => {
    addDesignHandler(dispatch);
    addClassicLayoutHandler(dispatch);
    addModernLayoutHandler(dispatch);
    addInheritCheckboxHandler(dispatch);
    addDesignFieldsetDisplayStatusHandler(dispatch, builderContainerId)

    if (['pro', 'premium'].includes(window.amastyCheckoutConfig?.edition)) {
        addSeparateBillingHandler(dispatch);
        addPrimaryAddressHandler(dispatch);
    }
};

const getCheckoutPresets = () => {
    return JSON.parse(document.getElementById(selectors.CHECKOUT_PRESETS_INPUT_ID).value);
};

const setCheckoutPresetsToField = (checkoutPresets) => {
    if (checkoutPresets) {
        document.getElementById(selectors.CHECKOUT_PRESETS_INPUT_ID).value = JSON.stringify(checkoutPresets);
    }
};

const setCheckoutFrontendConfigToField = (checkoutFrontendConfig) => {
    if (checkoutFrontendConfig) {
        document.getElementById(selectors.CHECKOUT_FRONTEND_CONFIG_INPUT_ID).value = JSON.stringify(checkoutFrontendConfig);
    }
};

const prepareActivePresetToSave = (activePreset, initialPresets, checkoutLayout) => {
    const { design, layout } = checkoutLayout;

    if (design && layout) {
        const presetsToSave = cloneDeep(initialPresets);

        presetsToSave[design][layout] = activePreset;
        setCheckoutPresetsToField(presetsToSave);
    }
};

const getSavedCheckoutBlocksData = () => {
    const savedCheckoutLayoutJson = document.getElementById(selectors.CHECKOUT_FRONTEND_CONFIG_INPUT_ID).value,
        savedCheckoutLayoutData = JSON.parse(document.getElementById(selectors.CHECKOUT_FRONTEND_CONFIG_INPUT_ID).value);
    let blocksData = {};

    if (!savedCheckoutLayoutJson) {
        return {};
    }

    savedCheckoutLayoutData.map(column => {
        return column.map(block => {
            blocksData[block.name] = {
                name: block.name,
                frontendTitle: block.title
            };
        });
    });

    return blocksData;
};

const initOperations = (dispatch, builderContainerId) => {
    const designSelect = document.getElementById(selectors.CHECKOUT_DESIGN_INPUT_ID);
    const classicLayoutSelect = document.getElementById(selectors.CHECKOUT_CLASSIC_DESIGN_LAYOUT_INPUT_ID);
    const modernLayoutSelect = document.getElementById(selectors.CHECKOUT_MODERN_DESIGN_LAYOUT_INPUT_ID);
    const billingSeparateSelect = document.getElementById(selectors.CHECKOUT_SEPARATE_BILLING_ADDRESS_INPUT_ID);
    const primaryAddressSelect = document.getElementById(selectors.CHECKOUT_SELECT_PRIMARY_ADDRESS_INPUT_ID);
    const designLabel = designSelect?.value && +designSelect.value === 1 ? 'modern' : defaultConfig.defaultLayout;
    const selectedLayout = designSelect?.value && +designSelect.value === 1
        ? modernLayoutSelect.value || defaultConfig.selectedLayout
        : classicLayoutSelect?.value || defaultConfig.selectedLayout;

    dispatch(ActionCreators.changeCheckoutDesignAction(designLabel));
    dispatch(ActionCreators.changeCheckoutLayoutAction(selectedLayout));
    dispatch(ActionDispatchers.changeCheckoutLayoutColumnsWidthDispatcher());

    addCheckoutSettingsHandlers(dispatch, builderContainerId);

    dispatch(ActionDispatchers.applyCheckoutConfigToBuilder(
        designLabel,
        selectedLayout
    ));

    if (window.amastyCheckoutConfig?.deliveryDateInstalled === true) {
        dispatch(ActionDispatchers.changeDeliveryDateState());
    }

    if (['pro', 'premium'].includes(window.amastyCheckoutConfig?.edition)) {
        dispatch(ActionCreators.changeCheckoutPrimaryAddress(primaryAddressSelect?.value));
        dispatch(ActionDispatchers.changeSeparateBillingState(!!+billingSeparateSelect?.value));
    }
};

export {
    initOperations,
    getCheckoutPresets,
    setCheckoutFrontendConfigToField,
    prepareActivePresetToSave,
    getSavedCheckoutBlocksData
};
