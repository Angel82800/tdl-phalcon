<span class="text-light-grey">View: </span>

<span class="hide-for-small-only">&nbsp;&nbsp;</span>

<ul class="view_choice dropdown menu" data-dropdown-menu>
  <li>
    <a href="javascript:void(0);">{{ type == 'device' ? 'By Devices' : 'By Users' }}</a>
    <ul class="menu">
      <li><a class="{{ type == 'device' ? 'active' : '' }}" href="{{ type == 'device' ? 'javascript:void(0);' : '/user-device?type=device' }}">By Devices</a></li>
      <li><a class="{{ type == 'user' ? 'active' : '' }}" href="{{ type == 'user' ? 'javascript:void(0);' : '/user-device?type=user' }}">By Users</a></li>
    </ul>
  </li>
</ul>

<!--
<a class="{{ type == 'device' ? 'active' : '' }}" href="{{ type == 'device' ? 'javascript:void(0);' : '/user-device?type=device' }}">By Devices</a>
<span class="hide-for-small-only">&nbsp;&nbsp;</span>
<a class="{{ type == 'user' ? 'active' : '' }}" href="{{ type == 'user' ? 'javascript:void(0);' : '/user-device?type=user' }}">By Users</a>
-->

<!-- Toggle when list view is ready
<span class="list-style-buttons">

  	<a href="javascript:void(0);" class="view-switcher disabled" data-type="list" data-tooltip data-allow-html="true" title="Coming Soon">
    	<i class="i-list icon-xl"></i>
  	</a>

  <span class="hide-for-small-only">&nbsp;</span>
  <a href="javascript:void(0);" class="view-switcher active" data-type="grid">
    <i class="i-grid icon-xl"></i>
  </a>
</span>
-->

<a class="button m-0 text-right" style="margin-left:1em;" href="/service/device">Add Users <span class="hide-for-small-only">or Devices</span></a>
