  <!-- {$NEWTLDS_PLUGINVERSION} -->

{if $NEWTLDS_ERRORS}
    <div class="alert alert-error">
      <p class="bold">{$clientareaerrors}</p>
      <ul>{$NEWTLDS_ERRORS}</ul>
    </div>
  {/if}

    {if $loggedin}
        {if $NEWTLDS_ENABLED}
              {if $NEWTLDS_PORTALACCOUNT}
                <!-- START WATCHLIST -->
                <div id="tldportal-root"></div>
                <script src="https://{$NEWTLDS_URLHOST}/api/embed?token={$NEWTLDS_LINK}" type="text/javascript"></script>
                <!-- END WATCHLIST -->
              {else}
                <strong>{$NEWTLDS_NOPORTALACCT}</strong>
                <br /><br /><br />
              {/if}
        {else}
          <strong>{$NEWTLDS_NOTENABLED}</strong>
          <br /><br /><br />
        {/if}
    {else}
      <strong>{$NEWTLDS_NOTLOGGEDIN}</strong>
      <br /><br /><br />
    {/if}

