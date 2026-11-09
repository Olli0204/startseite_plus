{* Startseite Plus – eine Zeile "Deal-Preis" im Formular der Newsletter-Deals. Parameter: rule (type, products, partners,
   productFeature, partnerFeature, price, label, labelEn), idx (Index oder "__i__" in der Vorlage für neue Zeilen),
   features (Merkmale mit Werten für die Merkmal-Auswahl) *}
<div class="sp-nld-rule" data-type="{$rule.type|escape:'html'}">
    <div class="sp-nld-rule-head">
        <select class="custom-select sp-nld-rule-type" name="nld_rules[{$idx}][type]" aria-label="Art des Deal-Preises">
            <option value="price"{if $rule.type === 'price'} selected{/if}>Festpreis je Artikel</option>
            <option value="set"{if $rule.type === 'set'} selected{/if}>Set-Preis (mit Partnerartikel)</option>
            <option value="setpct"{if $rule.type === 'setpct'} selected{/if}>Set-Rabatt in % (mit Partnerartikel)</option>
        </select>
        <div class="input-group sp-nld-rule-price">
            <input type="text" inputmode="decimal" class="form-control" name="nld_rules[{$idx}][price]"
                   value="{$rule.price|escape:'html'}" placeholder="z. B. 250" aria-label="Preis je Stück (brutto) oder Rabatt in Prozent">
            <div class="input-group-append">
                <span class="input-group-text" data-for="price set">€ je Stück</span>
                <span class="input-group-text" data-for="setpct">% Rabatt</span>
            </div>
        </div>
        <button type="button" class="btn btn-link px-2 sp-nld-rule-remove" title="Deal-Preis entfernen">
            <span class="icon-hover"><span class="fal fa-trash-alt"></span><span class="fas fa-trash-alt"></span></span>
        </button>
    </div>
    <p class="sp-nld-rule-text" data-for="price">Diese Artikel kosten je Stück den Preis. Ein Vaterartikel gilt für alle Varianten.</p>
    <p class="sp-nld-rule-text" data-for="set">Diese Artikel kosten je Stück den Preis, wenn einer der Set-Partner im Warenkorb liegt
        (höchstens so oft, wie Set-Partner im Warenkorb liegen).</p>
    <p class="sp-nld-rule-text" data-for="setpct">Diese Artikel kosten so viel Prozent weniger als ihr aktueller Shop-Preis (auch
        auf Sale-Preise), wenn ein Set-Partner im Warenkorb liegt – je Set-Partner ein Artikel. Ohne Set-Konfigurator.</p>
    <div class="sp-picker" data-sp-picker="products" data-max="50" data-parents-ok
         data-empty="Noch keine Artikel gewählt.">
        <input type="hidden" class="sp-picker-value" name="nld_rules[{$idx}][products]" value="{$rule.products|escape:'html'}">
        <div class="sp-picker-ui"></div>
    </div>
    <div class="sp-nld-rule-feature">
        <label class="sp-nld-rule-text" for="nld-rule-{$idx}-pf">… und/oder alle Artikel mit Merkmal:</label>
        <select class="custom-select" id="nld-rule-{$idx}-pf" name="nld_rules[{$idx}][product_feature]">
            <option value="0">– kein Merkmal –</option>
            {foreach $features as $feature}
                <optgroup label="{$feature.name|escape:'html'}">
                    {foreach $feature.values as $value}
                        <option value="{$value.id}"{if $rule.productFeature == $value.id} selected{/if}>{$feature.name|escape:'html'}: {$value.name|escape:'html'} ({$value.count} Artikel)</option>
                    {/foreach}
                </optgroup>
            {/foreach}
        </select>
    </div>
    <div class="sp-nld-rule-partners">
        <p class="sp-nld-rule-text">Set-Partner (z. B. das Board zur Bindung):</p>
        <div class="sp-picker" data-sp-picker="products" data-max="50" data-parents-ok
             data-empty="Noch keine Set-Partner gewählt.">
            <input type="hidden" class="sp-picker-value" name="nld_rules[{$idx}][partners]" value="{$rule.partners|escape:'html'}">
            <div class="sp-picker-ui"></div>
        </div>
        <div class="sp-nld-rule-feature">
            <label class="sp-nld-rule-text" for="nld-rule-{$idx}-sf">… und/oder alle Artikel mit Merkmal als Set-Partner:</label>
            <select class="custom-select" id="nld-rule-{$idx}-sf" name="nld_rules[{$idx}][partner_feature]">
                <option value="0">– kein Merkmal –</option>
                {foreach $features as $feature}
                    <optgroup label="{$feature.name|escape:'html'}">
                        {foreach $feature.values as $value}
                            <option value="{$value.id}"{if $rule.partnerFeature == $value.id} selected{/if}>{$feature.name|escape:'html'}: {$value.name|escape:'html'} ({$value.count} Artikel)</option>
                        {/foreach}
                    </optgroup>
                {/foreach}
            </select>
        </div>
        <div class="sp-nld-rule-labels">
            <input type="text" class="form-control" name="nld_rules[{$idx}][label]" maxlength="160"
                   value="{$rule.label|escape:'html'}" placeholder="Hinweis DE, z. B. −20 % mit jedem Snowboard (leer = automatisch)"
                   aria-label="Hinweistext Deutsch">
            <input type="text" class="form-control" name="nld_rules[{$idx}][label_en]" maxlength="160"
                   value="{$rule.labelEn|escape:'html'}" placeholder="Hinweis EN, z. B. 20% off with any snowboard"
                   aria-label="Hinweistext Englisch">
        </div>
    </div>
</div>
