{% if session.get('ftu') is empty or session.get('ftu')['userdevice'] is empty or session.get('ftu')['userdevice']['page_info'] is empty %}
<div class="ftu_notification pt-2 text-center">
  <div class="medium-8 medium-centered">
    <img src="/img/dashboard/devices-ftu.png" alt="Intro Image"/>

    <div class="medium-8 medium-centered">
      <h5 class="text-mid-grey pt-1">
        Whether you have a small staff or it's just you behind the wheel, this is the best place to manage which Todyl services go to which computers, laptops, and other devices.
      </h5>
    </div>

    <div class="pt-1">
      <a id="btn_pass_ftu" class="button">Got It, Thanks</a>
    </div>
  </div>
</div>
{% endif %}
