<h1>Your Weekly Protection Summary</h1>
<p>
{{ firstName ? firstName ~ ', here' : 'Here' }} is a quick summary of what happened this week, {{ range }}
</p>

<div style="margin: 0 0; width: 620px;">&nbsp;</div>

<table width="636">
  <tr style="padding:32px 0px" align="center">
    <td style="padding-bottom:16px;">
      <img src="{{ absolutePath }}img/email/secure-data.png" alt="Secured Data">
    </td>
    <td style="padding-bottom:16px;">
        <h1 style="color:#2BBB5A;font-size:64px;font-weight:300;text-align:left;">{{ data_protected['value'] }} {{ data_protected['unit'] }}<img src="{{ absolutePath }}img/email/{{ percentage >= 0 ? 'good-up.png' : 'good-down.png' }}"></h1>
        <p style="color:#828588;margin:0;text-align:left;">
          of data was protected.<br><b>That’s {{ percentage >= 0 ? ('more than') : ('less than') }} last week.</b>
        </p>
    </td>
  </tr>
  <tr valign="center">
    <td style="padding-bottom:16px;">
        <h1 style="color:#828588;font-size:64px;font-weight:300">{{ traffic_blocked }}</h1>
        <p style="color:#828588;margin:0;">
          potential threats were blocked. {{ blocked_history }}
        </p>
    </td>
    <td style="padding-bottom:16px;">
      <img src="{{ absolutePath }}img/email/threats-blocked.png">
    </td>
  </tr>
  <tr valign="center">
    <td>
      <img src="{{ absolutePath }}img/email/threat-indicators.png" alt="Theat Indicators Added">
    </td>
    <td>
      <h1 style="color:#F8981D;font-size:64px;font-weight:300">{{ threats_added }}<img src="{{ absolutePath }}img/email/plus.png"></h1>
        <p style="color:#828588;margin:0;">
          new threat indicators were added. Roughly <b>{{ threats_percentage }}% of these are missed by other security products</b>.
        </p>
    </td>
  </tr>
</table>

<table width="636" style="padding:32px 0 10px;">
 <tr>
    <td colspan="2">
      <p>
These threats were blocked while securing your everyday device and network activity.<br>
Todyl continuously monitors and defends you against the latest threats.<br>
If you have any questions or would like more information click the button below to visit your dashboard. 

      </p>
    </td>
  </tr>
</table>

<p>
  <table style="padding: 10px 0;" width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
      <td>
        <table border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td align="center" bgcolor="#2b80bb"><a href="{{ absolutePath }}dashboard?utm_source=WeeklyEmail&utm_medium=AutomatedEmail&utm_campaign=Ongoing" target="_blank" style="font-size: 16px; color: #ffffff; text-decoration: none; padding: 15px 40px; border: 1px solid #2b80bb; display: inline-block;">Visit Your Dashboard</a></td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</p>

<div style="margin: 3px 0; width: 620px;">&nbsp;</div>
