<p style="padding: 16px 0px;">
Hi {{ firstName | default('') }},<br /><br />
Unfortunately, your most recent invoice payment for {{ amount_due }} was declined. This could be due to a change in your card number, your card expiring, cancellation of your credit card, or the card issuer not recognizing the payment and therefore taking action to prevent it.
</p>

<p>Please update your payment information as soon as possible by logging in here :</p>

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
