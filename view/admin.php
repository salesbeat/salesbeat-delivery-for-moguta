<div class="widget-body section-<?= $pluginName; ?>">
    <div class="widget-panel-holder">
        <div class="widget-panel">
            <form class="base-setting" name="base-setting" method="POST">
                <ul class="tabs custom-tabs widget-tabs" data-tabs-list>
                    <li class="tabs-title is-active">
                        <a href="#" data-tab="widget-<?= $pluginName; ?>" data-tab-id="settings">
                            <i class="fa fa-cog fa-fw"></i> <span><?= $lang['sb_tab_settings']; ?></span>
                        </a>
                    </li>

                    <li class="tabs-title">
                        <a href="#" data-tab="widget-<?= $pluginName; ?>" data-tab-id="pay-system">
                            <i class="fa fa-credit-card fa-fw"></i> <span><?= $lang['sb_tab_pay_systems']; ?></span>
                        </a>
                    </li>

                    <li class="tabs-title">
                        <a href="#" data-tab="widget-<?= $pluginName; ?>" data-tab-id="property">
                            <i class="fa fa-th-list fa-fw"></i> <span><?= $lang['sb_tab_properties']; ?></span>
                        </a>
                    </li>
                </ul>

                <div class="tabs-content">
                    <div class="tabs-panel is-active" data-tab-content="widget-<?= $pluginName; ?>"
                         data-tab-content-id="settings">
                        <div class="row large-8 columns inline-label">
                            <h2 class="dashed"><?= $lang['sb_section_settings']; ?></h2>

                            <div class="row sett-line">
                                <div class="small-12 medium-3 columns dashed">
                                    <label for="input-api-token" class="with-tooltip"><?= $lang['sb_field_api_token']; ?></label>
                                </div>
                                <div class="small-12 medium-9 columns">
                                    <?php if (isset($options['api_token'])): ?>
                                        <input type="text" name="api_token" value="<?= $options['api_token']; ?>"
                                               placeholder="<?= $lang['sb_field_api_token']; ?>" id="input-api-token">
                                    <?php else: ?>
                                        <input type="text" name="api_token" placeholder="<?= $lang['sb_field_api_token']; ?>"
                                               id="input-api-token">
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="row sett-line">
                                <div class="small-12 medium-3 columns">
                                    <label for="input-secret-token" class="with-tooltip"><?= $lang['sb_field_secret_token']; ?></label>
                                </div>
                                <div class="small-12 medium-9 columns">
                                    <?php if (isset($options['secret_token'])): ?>
                                        <input type="text" name="secret_token" value="<?= $options['secret_token']; ?>"
                                               placeholder="<?= $lang['sb_field_secret_token']; ?>" id="input-secret-token">
                                    <?php else: ?>
                                        <input type="text" name="secret_token" placeholder="<?= $lang['sb_field_secret_token']; ?>"
                                               id="input-secret-token">
                                    <?php endif; ?>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="tabs-panel" data-tab-content="widget-<?= $pluginName; ?>"
                         data-tab-content-id="pay-system">
                        <div class="row large-8 columns inline-label">
                            <h2 class="dashed"><?= $lang['sb_section_pay_systems']; ?></h2>

                            <div class="row flex">
                                <div class="small-12 medium-3 columns dashed">
                                    <label class="with-tooltip"><?= $lang['sb_field_pay_systems_cash']; ?></label>
                                </div>
                                <div class="small-12 medium-9 columns">
                                    <div class="admin-options__list">
                                        <?php foreach ($arPayments as $arPayment): ?>
                                            <label class="admin-options">
                                                <div class="checkbox admin-options__inner">
                                                    <?php if (isset($options['pay_systems_cash']) && in_array($arPayment['id'], $options['pay_systems_cash'])): ?>
                                                        <input type="checkbox" name="pay_systems_cash" value="true"
                                                               id="pay-systems-cash-<?= $arPayment['id']; ?>"
                                                               class="admin-options__checkbox" checked="checked"
                                                               data-id="<?= $arPayment['id']; ?>">
                                                    <?php else: ?>
                                                        <input type="checkbox" name="pay_systems_cash" value="false"
                                                               id="pay-systems-cash-<?= $arPayment['id']; ?>"
                                                               class="admin-options__checkbox"
                                                               data-id="<?= $arPayment['id']; ?>">
                                                    <?php endif; ?>

                                                    <label class="admin-options__label" for="pay-systems-cash-<?= $arPayment['id']; ?>"></label>
                                                    <span class="admin-options__title"><?= $arPayment['name']; ?></span>
                                                </div>
                                            </label>
                                        <? endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="row flex">
                                <div class="small-12 medium-3 columns dashed">
                                    <label class="with-tooltip"><?= $lang['sb_field_pay_systems_card']; ?></label>
                                </div>
                                <div class="small-12 medium-9 columns">
                                    <div class="admin-options__list">
                                        <?php foreach ($arPayments as $arPayment): ?>
                                            <label class="admin-options">
                                                <div class="checkbox admin-options__inner">
                                                    <?php if (isset($options['pay_systems_card']) && in_array($arPayment['id'], $options['pay_systems_card'])): ?>
                                                        <input type="checkbox" name="pay_systems_card" value="true"
                                                               id="pay-systems-card-<?= $arPayment['id']; ?>"
                                                               class="admin-options__checkbox" checked="checked"
                                                               data-id="<?= $arPayment['id']; ?>">
                                                    <?php else: ?>
                                                        <input type="checkbox" name="pay_systems_card" value="false"
                                                               id="pay-systems-card-<?= $arPayment['id']; ?>"
                                                               class="admin-options__checkbox"
                                                               data-id="<?= $arPayment['id']; ?>">
                                                    <?php endif; ?>

                                                    <label class="admin-options__label" for="pay-systems-card-<?= $arPayment['id']; ?>"></label>
                                                    <span class="admin-options__title"><?= $arPayment['name']; ?></span>
                                                </div>
                                            </label>
                                        <? endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="row flex">
                                <div class="small-12 medium-3 columns">
                                    <label class="with-tooltip"><?= $lang['sb_field_pay_systems_online']; ?></label>
                                </div>
                                <div class="small-12 medium-9 columns">
                                    <div class="admin-options__list">
                                        <?php foreach ($arPayments as $arPayment): ?>
                                            <label class="admin-options">
                                                <div class="checkbox admin-options__inner">
                                                    <?php if (isset($options['pay_systems_online']) && in_array($arPayment['id'], $options['pay_systems_online'])): ?>
                                                        <input type="checkbox" name="pay_systems_online" value="true"
                                                               id="pay-systems-online-<?= $arPayment['id']; ?>"
                                                               class="admin-options__checkbox" checked="checked"
                                                               data-id="<?= $arPayment['id']; ?>">
                                                    <?php else: ?>
                                                        <input type="checkbox" name="pay_systems_online" value="false"
                                                               id="pay-systems-online-<?= $arPayment['id']; ?>"
                                                               class="admin-options__checkbox"
                                                               data-id="<?= $arPayment['id']; ?>">
                                                    <?php endif; ?>

                                                    <label class="admin-options__label" for="pay-systems-online-<?= $arPayment['id']; ?>"></label>
                                                    <span class="admin-options__title"><?= $arPayment['name']; ?></span>
                                                </div>
                                            </label>
                                        <? endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tabs-panel" data-tab-content="widget-<?= $pluginName; ?>"
                         data-tab-content-id="property">
                        <div class="row large-8 columns inline-label">
                            <h2 class="dashed"><?= $lang['sb_section_properties']; ?></h2>

                            <div class="row sett-line">
                                <div class="small-12 medium-3 columns dashed">
                                    <label class="with-tooltip"><?= $lang['sb_field_property_width']; ?></label>
                                </div>
                                <div class="small-12 medium-9 columns">
                                    <select class="option last-items-dropdown" name="property_width">
                                        <option value=""><?= $lang['sb_field_property_width_default']; ?></option>
                                        <?php
                                        foreach ($arProperties as $arProperty):
                                            if (isset($options['property_width']) && $arProperty['id'] == $options['property_width']):
                                                ?>
                                                <option value="<?= $arProperty['id']; ?>"
                                                        selected><?= $arProperty['name']; ?></option>
                                            <?php else: ?>
                                                <option value="<?= $arProperty['id']; ?>"><?= $arProperty['name']; ?></option>
                                            <?php
                                            endif;
                                        endforeach;
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row sett-line">
                                <div class="small-12 medium-3 columns dashed">
                                    <label class="with-tooltip"><?= $lang['sb_field_property_height']; ?></label>
                                </div>
                                <div class="small-12 medium-9 columns">
                                    <select class="option last-items-dropdown" name="property_height">
                                        <option value=""><?= $lang['sb_field_property_height_default']; ?></option>
                                        <?php
                                        foreach ($arProperties as $arProperty):
                                            if (isset($options['property_height']) && $arProperty['id'] == $options['property_height']):
                                                ?>
                                                <option value="<?= $arProperty['id']; ?>"
                                                        selected><?= $arProperty['name']; ?></option>
                                            <?php else: ?>
                                                <option value="<?= $arProperty['id']; ?>"><?= $arProperty['name']; ?></option>
                                            <?php
                                            endif;
                                        endforeach;
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row sett-line">
                                <div class="small-12 medium-3 columns">
                                    <label class="with-tooltip"><?= $lang['sb_field_property_depth']; ?></label>
                                </div>
                                <div class="small-12 medium-9 columns">
                                    <select class="option last-items-dropdown" name="property_depth">
                                        <option value=""><?= $lang['sb_field_property_depth_default']; ?></option>
                                        <?php
                                        foreach ($arProperties as $arProperty):
                                            if (isset($options['property_depth']) && $arProperty['id'] == $options['property_depth']):
                                                ?>
                                                <option value="<?= $arProperty['id']; ?>"
                                                        selected><?= $arProperty['name']; ?></option>
                                            <?php else: ?>
                                                <option value="<?= $arProperty['id']; ?>"><?= $arProperty['name']; ?></option>
                                            <?php
                                            endif;
                                        endforeach;
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <h2 class="dashed"><?= $lang['sb_section_units']; ?></h2>

                            <div class="row sett-line">
                                <div class="small-12 medium-3 columns dashed">
                                    <label class="with-tooltip"><?= $lang['sb_field_unit_weight']; ?></label>
                                </div>
                                <div class="small-12 medium-9 columns">
                                    <select class="option last-items-dropdown" name="unit_weight">
                                        <?php
                                        $unitWeight = ['gr', 'kg'];
                                        foreach ($unitWeight as $unit):
                                            if (isset($options['unit_weight']) && $unit == $options['unit_weight']):
                                                ?>
                                                <option value="<?= $unit ?>"
                                                        selected><?= $lang['sb_field_unit_weight_' . $unit]; ?></option>
                                            <?php else: ?>
                                                <option value="<?= $unit ?>"><?= $lang['sb_field_unit_weight_' . $unit]; ?></option>
                                            <?php
                                            endif;
                                        endforeach;
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row sett-line">
                                <div class="small-12 medium-3 columns">
                                    <label class="with-tooltip"><?= $lang['sb_field_unit_dimensions']; ?></label>
                                </div>
                                <div class="small-12 medium-9 columns">
                                    <select class="option last-items-dropdown" name="unit_dimensions">
                                        <?php
                                        $unitDimensions = ['mm', 'sm', 'm'];
                                        foreach ($unitDimensions as $unit):
                                            if (isset($options['unit_dimensions']) && $unit == $options['unit_dimensions']):
                                                ?>
                                                <option value="<?= $unit ?>"
                                                        selected><?= $lang['sb_field_unit_dimensions_' . $unit]; ?></option>
                                            <?php else: ?>
                                                <option value="<?= $unit ?>"><?= $lang['sb_field_unit_dimensions_' . $unit]; ?></option>
                                            <?php
                                            endif;
                                        endforeach;
                                        ?>
                                    </select>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </form>
            <div class="clear"></div>

            <a role="button" href="javascript:void(0);" class="base-setting-save custom-btn button success">
                <span><i class="fa fa-floppy-o" aria-hidden="true"></i><?= $lang['sb_button_setting_save']; ?></span>
            </a>
            <div class="clear"></div>
        </div>
    </div>
</div>