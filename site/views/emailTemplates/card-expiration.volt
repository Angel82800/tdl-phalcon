<p style="padding: 16px 0px;">
Hi {{ firstName | default('') }},<br /><br />
The credit card associated with your account is set to expire soon.<br />
Please update it by {{ nextInvoiceDate }} to avoid a lapse in protection.
</p>

<p>
  <table width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
      <td>
        <table border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td align="center" bgcolor="#fff"><a href="{{ absolutePath }}account" target="_blank" style="font-size: 16px; color: #ffffff; text-decoration: none; padding: 12px 50px; border: 1px solid #2b80bb; display: inline-block; border-radius: 30px; background-color: #2b80bb;">Update Your Info</a></td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</p>

<div style="margin: 3px 0; width: 620px;">&nbsp;</div>
