<table align="center">
  <tr>
    <td><form action="clientarea.php?action=productdetails" method="post">
    <input type="hidden" name="id" value="{$serviceid}" />
    <input type="hidden" name="modop" value="custom" />
    <input type="hidden" name="a" value="reboot" />
    <input type="submit" value="Reboot VPS" class="button" />
</form></td>
    <td><form action="clientarea.php?action=productdetails" method="post">
    <input type="hidden" name="id" value="{$serviceid}" />
    <input type="hidden" name="modop" value="custom" />
    <input type="hidden" name="a" value="startup" />
    <input type="submit" value="Startup VPS" class="button" />
</form></td>
    <td><form action="clientarea.php?action=productdetails" method="post">
    <input type="hidden" name="id" value="{$serviceid}" />
    <input type="hidden" name="modop" value="custom" />
    <input type="hidden" name="a" value="shutdown" />
    <input type="submit" value="Shutdown VPS" class="button" />
</form></td>
  </tr>
</table>