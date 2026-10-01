{* Startseite Plus – eine Zeile "Deal-Preis" im Formular der Newsletter-Deals. Parameter: rule (type, products, partners,
   price), idx (Index oder "__i__" in der Vorlage für neue Zeilen) *}
<div class="sp-nld-rule" data-type="{$rule.type|escape:'html'}">
    <div class="sp-nld-rule-head">
        <select class="custom-select sp-nld-rule-type" name="nld_rules[{$idx}][type]" aria-label="Art des Deal-Preises">
            <option value="price"{if $rule.type === 'price'} selected{/if}>Festpreis je Artikel</option>
            <option value="set"{if $rule.type === 'set'} selected{/if}>Set-Preis (mit Partnerartikel)</option>
        </select>
        <div class="input-group sp-nld-rule-price">
            <input type="text" inputmode="decimal" class="form-control" name="nld_rules[{$idx}][price]"
                   value="{$rule.price|escape:'html'}" placeholder="z. B. 250" aria-label="Preis je Stück (brutto)">
            <div class="input-group-append"><span class="input-group-text">€ je Stück</span></div>
        </div>
        <button type="button" class="btn btn-link px-2 sp-nld-rule-remove" title="Deal-Preis entfernen">
            <span class="icon-hover"><span class="fal fa-trash-alt"></span><span class="fas fa-trash-alt"></span></span>
        </button>
    </div>
    <p class="sp-nld-rule-text" data-for="price">Diese Artikel kosten je Stück den Preis. Ein Vaterartikel gilt für alle Varianten.</p>
    <p class="sp-nld-rule-text" data-for="set">Diese Artikel kosten je Stück den Preis, wenn einer der Set-Partner im Warenkorb liegt
        (höchstens so oft, wie Set-Partner im Warenkorb liegen).</p>
    <div class="sp-picker" data-sp-picker="products" data-max="50" data-parents-ok
         data-empty="Noch keine Artikel gewählt.">
        <input type="hidden" class="sp-picker-value" name="nld_rules[{$idx}][products]" value="{$rule.products|escape:'html'}">
        <div class="sp-picker-ui"></div>
    </div>
    <div class="sp-nld-rule-partners">
        <p class="sp-nld-rule-text">Set-Partner (z. B. das Board zur Bindung):</p>
        <div class="sp-picker" data-sp-picker="products" data-max="50" data-parents-ok
             data-empty="Noch keine Set-Partner gewählt.">
            <input type="hidden" class="sp-picker-value" name="nld_rules[{$idx}][partners]" value="{$rule.partners|escape:'html'}">
            <div class="sp-picker-ui"></div>
        </div>
    </div>
</div>
