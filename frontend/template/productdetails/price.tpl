{* Startseite Plus – Platzhalter für den Newsletter-Deal-Preis (Artikelliste, Artikelseite). Er bleibt unsichtbar; je nach
   Darstellung der Deal-Seite setzt newsletter-deal.js eine Zeile in den Preisblock, macht den Newsletter-Preis zum
   Hauptpreis oder setzt eine Marke ans Produktbild. data-sp-nld-current = aktueller Bruttopreis (Hinweis nur, wenn günstiger).
   Wichtig: Das Markup ist für alle Besucher gleich (nur Artikel mit laufendem Deal bekommen einen leeren, versteckten
   Platzhalter). Ob und welcher Preis erscheint, entscheidet newsletter-deal.js per IO in der Sitzung des Kunden –
   ein Seitencache (LiteSpeed im Live-Shop) darf keine freigeschalteten Preise an andere Kunden ausliefern. *}
{block name='productdetails-price' append}
    {if !empty($spNlDealIDs) && isset($Artikel) && !empty($Artikel->kArtikel)
        && (isset($spNlDealIDs[$Artikel->kArtikel]) || (!empty($Artikel->kVaterArtikel) && isset($spNlDealIDs[$Artikel->kVaterArtikel])))}
        {if empty($spNlDealAssetsDone)}
            <link rel="stylesheet" href="{$spNlDealCss|escape:'html'}">
            <script src="{$spNlDealJs|escape:'html'}" defer></script>
            {assign var=spNlDealAssetsDone value=true scope='global'}
        {/if}
        <div class="sp-nld-price{if ($tplscope|default:'') === 'detail'} sp-nld-price--detail{/if}" hidden
             data-sp-nld-product="{$Artikel->kArtikel|intval}" data-sp-nld-parent="{$Artikel->kVaterArtikel|default:0|intval}"
             data-sp-nld-current="{$Artikel->Preise->fVKBrutto|default:0|string_format:'%.2F'}"></div>
    {/if}
{/block}
