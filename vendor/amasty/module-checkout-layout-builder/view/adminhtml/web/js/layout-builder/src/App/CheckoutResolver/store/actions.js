import cloneDeep from 'lodash/cloneDeep';
import { ActionCreators as BuilderActionCreators } from '@layoutBuilder/store/actions';
import StoreUtils from './utils';

const CHECKOUT_LAYOUT_CONFIG_FIELD_INHERIT_CHECKBOX_ID = 'amasty_checkout_design_layout_frontend_layout_config_inherit';

const ActionTypes = {
    SET_TRANSLATIONS_TO_STATE_ACTION: 'SET_TRANSLATIONS_TO_STATE_ACTION',
    CHANGE_CHECKOUT_DESIGN_ACTION: 'CHANGE_CHECKOUT_DESIGN_ACTION',
    CHANGE_CHECKOUT_LAYOUT_ACTION: 'CHANGE_CHECKOUT_LAYOUT_ACTION',
    CHANGE_CHECKOUT_LAYOUT_COLUMNS_WIDTH_ACTION: 'CHANGE_CHECKOUT_LAYOUT_COLUMNS_WIDTH_ACTION',
    CHANGE_CHECKOUT_PRESETS_ACTION: 'CHANGE_CHECKOUT_PRESETS_ACTION',
    CHANGE_CHECKOUT_FRONTEND_CONFIG_ACTION: 'CHANGE_CHECKOUT_FRONTEND_CONFIG_ACTION',
    CHANGE_ACTIVE_PRESET_ACTION: 'CHANGE_ACTIVE_PRESET_ACTION',
    SAVE_INITIAL_PRESETS_ACTION: 'SAVE_INITIAL_PRESETS_ACTION',
    SAVE_CHECKOUT_PRESETS_MAP_ACTION: 'SAVE_CHECKOUT_PRESETS_MAP_ACTION',
    CHANGE_CHECKOUT_PRIMARY_ADDRESS: 'CHANGE_CHECKOUT_PRIMARY_ADDRESS'
};

const ActionCreators = {
    setTranslationsToState: (translations) => ({
        type: ActionTypes.SET_TRANSLATIONS_TO_STATE_ACTION,
        payload: translations
    }),
    changeCheckoutPresetsAction: (checkoutPresets) => ({
        type: ActionTypes.CHANGE_CHECKOUT_PRESETS_ACTION,
        payload: checkoutPresets
    }),
    saveCheckoutPresetsMapAction: (checkoutPresetsMap) => ({
        type: ActionTypes.SAVE_CHECKOUT_PRESETS_MAP_ACTION,
        payload: checkoutPresetsMap
    }),
    changeCheckoutPrimaryAddress: (primaryAddress) => ({
        type: ActionTypes.CHANGE_CHECKOUT_PRIMARY_ADDRESS,
        payload: primaryAddress
    }),
    saveInitialPresetsAction: (checkoutPresets) => ({
        type: ActionTypes.SAVE_INITIAL_PRESETS_ACTION,
        payload: checkoutPresets
    }),
    changeCheckoutDesignAction: (newCheckoutDesign) => ({
        type: ActionTypes.CHANGE_CHECKOUT_DESIGN_ACTION,
        payload: newCheckoutDesign
    }),
    changeCheckoutLayoutAction: (newCheckoutLayout) => ({
        type: ActionTypes.CHANGE_CHECKOUT_LAYOUT_ACTION,
        payload: newCheckoutLayout
    }),
    changeCheckoutLayoutColumnsWidthAction: (columnsWidth) => ({
        type: ActionTypes.CHANGE_CHECKOUT_LAYOUT_COLUMNS_WIDTH_ACTION,
        payload: columnsWidth
    }),
    changeCheckoutFrontendConfig: (newCheckoutConfig) => ({
        type: ActionTypes.CHANGE_CHECKOUT_FRONTEND_CONFIG_ACTION,
        payload: newCheckoutConfig
    }),
    changeActivePreset: (activePreset) => ({
        type: ActionTypes.CHANGE_ACTIVE_PRESET_ACTION,
        payload: activePreset
    })
};

// Redux Thunk Action Creators
const ActionDispatchers = {
    loadCheckoutPresetsAction: (presets) => (dispatch) => {
        const initialPresets = cloneDeep(presets);
        const presetsDesigns = Object.keys(initialPresets);
        const presetsMap = [];

        presetsDesigns.map(design =>
            Object.keys(initialPresets[design]).map(key => presetsMap.push([design, key]))
        );

        dispatch(ActionCreators.changeCheckoutPresetsAction(presets));
        dispatch(ActionCreators.saveInitialPresetsAction(initialPresets));
        dispatch(ActionCreators.saveCheckoutPresetsMapAction(presetsMap));
    },

    applyCheckoutConfigToBuilder: (design, layout, inheritStatus) => (dispatch, getState) => {
        const checkoutPreset = getState().checkoutResolver.checkoutPresets[design][layout];
        const inheritCheckbox = document.getElementById(CHECKOUT_LAYOUT_CONFIG_FIELD_INHERIT_CHECKBOX_ID);
        const inheritStatus = inheritCheckbox ? inheritCheckbox.checked : false;

        dispatch(BuilderActionCreators.changeBuilderConfigAction(checkoutPreset));
        dispatch(ActionCreators.changeCheckoutLayoutAction(layout));
        dispatch(ActionDispatchers.changeCheckoutLayoutColumnsWidthDispatcher());
        dispatch(BuilderActionCreators.toggleBuilderStatus(!inheritStatus));
    },

    changeSeparateBillingState: separateState => (dispatch, getState) => {
        const state = getState();
        const primaryAddress = state.checkoutResolver.primaryAddress;

        dispatch(ActionDispatchers.addItemToBuilder('shipping_address', 'Shipping Address'));

        if (separateState) {
            dispatch(ActionDispatchers.addItemToBuilder('billing_address', 'Billing Address'));
        } else {
            dispatch(ActionDispatchers.deleteItemFromBuilder('billing_address'));
            dispatch(ActionDispatchers.changeCheckoutPrimaryAddressDispatcher(primaryAddress));
        }
    },

    changeCheckoutPrimaryAddressDispatcher: address => (dispatch) => {
        if (address === 'shipping') {
            dispatch(ActionDispatchers.addItemToBuilder('shipping_address', 'Shipping Address'));
            dispatch(ActionDispatchers.deleteItemFromBuilder('billing_address'));
        } else {
            dispatch(ActionDispatchers.addItemToBuilder('billing_address', 'Billing Address'));
            dispatch(ActionDispatchers.deleteItemFromBuilder('shipping_address'));
        }

        dispatch(ActionCreators.changeCheckoutPrimaryAddress(address));
    },

    addItemToBuilder: (index, title) => (dispatch, getState) => {
        const state = getState();
        const { presetsMap, checkoutPresets, checkoutLayout } = state.checkoutResolver;

        presetsMap.map(presetMap => {
            const [design, layout] = presetMap;
            const preset = checkoutPresets[design][layout];
            const isItemExist = preset.layout.filter((layoutItem) => {
                return layoutItem.i === index
            }).length;

            if (isItemExist) {
                return;
            }

            preset.layout.push(
                StoreUtils.generateNewBuilderItem(index, title, preset.columnsWidth[0], 1)
            );
        });

        const activePreset = checkoutPresets[checkoutLayout.design][checkoutLayout.layout];

        dispatch(ActionCreators.changeCheckoutPresetsAction(checkoutPresets));
        dispatch(BuilderActionCreators.changeBuilderConfigAction(activePreset));
    },
    deleteItemFromBuilder: (index) => (dispatch, getState) => {
        const state = getState();
        const { presetsMap, checkoutPresets, checkoutLayout } = state.checkoutResolver;

        presetsMap.map(presetMap => {
            const [design, layout] = presetMap;
            const preset = checkoutPresets[design][layout];

            preset.layout = preset.layout.filter((layoutItem) => {
                return layoutItem.i !== index
            });
        });

        const activePreset = checkoutPresets[checkoutLayout.design][checkoutLayout.layout];

        dispatch(ActionCreators.changeCheckoutPresetsAction(checkoutPresets));
        dispatch(BuilderActionCreators.changeBuilderConfigAction(activePreset));
    },
    changeCheckoutFrontendConfigFromBuilderConfig: () => (dispatch, getState) => {
        const state = getState();
        const { layoutBuilderConfig } = state.layoutBuilder;
        const { checkoutLayout } = state.checkoutResolver;
        const { checkoutItemsConfig } = state.checkoutItems;

        if (checkoutLayout.columnsWidth && layoutBuilderConfig) {
            const checkoutColumnsConfig = StoreUtils.getCheckoutConfigFromBuilderConfig({
                builderConfig: layoutBuilderConfig,
                columnsWidth: checkoutLayout.columnsWidth
            });

            const checkoutConfig = checkoutColumnsConfig.map((column) => {
                return column.map((blockName) => {
                    return {
                        name: blockName,
                        title: checkoutItemsConfig[blockName] ? checkoutItemsConfig[blockName].frontendTitle : ''
                    };
                });
            });

            dispatch(ActionCreators.changeCheckoutFrontendConfig(checkoutConfig));
        }
    },
    setBlocksTitlesToCheckoutFrontendConfig: () => (dispatch, getState) => {
        const state = getState();
        const { checkoutFrontendConfig } = state.checkoutResolver;
        const { checkoutItemsConfig } = state.checkoutItems;

        const checkoutConfig = checkoutFrontendConfig.map((column) => {
            return column.map((block) => ({
                ...block,
                title: checkoutItemsConfig[block.name] ? checkoutItemsConfig[block.name].frontendTitle : ''
            }));
        });

        dispatch(ActionCreators.changeCheckoutFrontendConfig(checkoutConfig));
    },
    setBuilderConfigToCheckoutPresets: () => (dispatch, getState) => {
        const state = getState();
        const { layoutBuilderConfig } = state.layoutBuilder;
        const { checkoutLayout, checkoutPresets } = state.checkoutResolver;
        const builderLayout = layoutBuilderConfig.layout;
        const { design, layout } = checkoutLayout;

        if (design && layout) {
            const currentCheckoutPreset = checkoutPresets[design][layout];
            const currentCheckoutLayout = currentCheckoutPreset.layout;

            const newLayout = currentCheckoutLayout.map((presetItem) => {
                const builderItem = builderLayout.find((item) => item.i === presetItem.i);

                return { ...presetItem, ...builderItem };
            });

            checkoutPresets[design][layout] = {...currentCheckoutPreset, layout: newLayout};

            dispatch(ActionCreators.changeCheckoutPresetsAction(checkoutPresets));
        }
    },
    changeCheckoutLayoutColumnsWidthDispatcher: () => (dispatch, getState) => {
        const state = getState();
        const { design, layout } = state.checkoutResolver.checkoutLayout;

        if (design && layout) {
            const checkoutPreset = state.checkoutResolver.checkoutPresets[design][layout];
            const { columnsWidth } = checkoutPreset;

            dispatch(ActionCreators.changeCheckoutLayoutColumnsWidthAction(columnsWidth));
        }
    },
    changeDeliveryDateState: () => (dispatch, getState) => {
        dispatch(ActionDispatchers.addItemToBuilder('delivery', 'Delivery'));
    },
};

export { ActionTypes, ActionCreators, ActionDispatchers };
