<table style="padding: 10px 0;" width="636" style="padding:32px 0 10px;">
  <tr>
    <td align=center>
      <img src="{{ absolutePath }}img/email/add-protection.png">
    </td>
  </tr>
</table>

{% if is_ftu is not empty %}

<p style="color: #2b80bb; font-size: 27px; margin: 10px 0 0;">Welcome to Todyl. Let’s Protect Your First Device.</p>
<p>It only takes a minute to set up Todyl Defender.<br />Simply click the button below from the device you want to protect to get started.</p>

{% else %}

<p style="color: #2b80bb; font-size: 27px; margin: 10px 0 0;">Protect a New Device</p>
<p>To activate Todyl Protection, click this button from the device you wish to protect.</p>

{% endif %}

<p>
  <table style="padding: 10px 0;" width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
      <td>
        <table border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td align="center" bgcolor="#2b80bb"><a href="{{ absolutePath }}dnld/{{ GUID }}" target="_blank" style="font-size: 16px; color: #ffffff; text-decoration: none; padding: 15px 40px; border: 1px solid #2b80bb; display: inline-block;">Protect this Device</a></td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</p>

<p>Note: For security, this link will expire in 24 hours.</p>

<div style="margin: 3px 0; width: 620px;">&nbsp;</div>
