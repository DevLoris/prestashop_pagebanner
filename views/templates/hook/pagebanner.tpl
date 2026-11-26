{**
 * Flaveo Page Banner
 *
 * @author    Loris Pinna <https://lorispinna.com>
 * @copyright Loris Pinna
 *}
<div class="flaveo-pagebanners" id="flaveo-pagebanners">
  {foreach from=$flaveo_banners item=banner}
    {capture name='fpb_content'}{if $banner.link}<a class="flaveo-pagebanner__link" href="{$banner.link|escape:'html':'UTF-8'}">{$banner.message nofilter}</a>{else}{$banner.message nofilter}{/if}{/capture}
    <div
      class="flaveo-pagebanner{if $banner.marquee} flaveo-pagebanner--marquee{/if}{if $banner.closable} flaveo-pagebanner--closable{/if}"
      id="flaveo-pagebanner-{$banner.id|intval}"
      style="--fpb-bg: {$banner.bg_color|escape:'html':'UTF-8'}; --fpb-color: {$banner.text_color|escape:'html':'UTF-8'};"
      data-cookie="{$banner.cookie|escape:'html':'UTF-8'}"
      data-cookie-days="{$banner.cookie_days|intval}"
      {if $banner.marquee}data-speed="{$banner.marquee_speed|intval}"{/if}
    >
      {if $banner.marquee}
        <div class="flaveo-pagebanner__marquee">
          <div class="flaveo-pagebanner__track">
            <span class="flaveo-pagebanner__item">{$smarty.capture.fpb_content nofilter}</span>
          </div>
        </div>
      {else}
        <div class="flaveo-pagebanner__content">{$smarty.capture.fpb_content nofilter}</div>
      {/if}
      {if $banner.closable}
        <button type="button" class="flaveo-pagebanner__close" aria-label="{l s='Fermer' d='Modules.Flaveopagebanner.Shop'}">&times;</button>
      {/if}
    </div>
  {/foreach}
</div>
<script>
  (function () {
    var c = document.getElementById('flaveo-pagebanners');
    if (c && document.body) {
      document.body.style.setProperty('--flaveo-banner-height', Math.round(c.getBoundingClientRect().height) + 'px');
    }
  })();
</script>
