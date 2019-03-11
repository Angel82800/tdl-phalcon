<div class="panels_container devices-container">
	<div class="row expanded">
		<div class="small-12 columns">
			<div class="panel clearfix">
				<div class="small-12 medium-6 columns kill-padding">
					<h4 class="kill-margin">{{ title }}</h4>
				</div>
				<div class="small-12 medium-6 columns medium-text-right kill-padding pt-half">
					<a class="{{ type == 'active' ? 'dark' : '' }}" href="{{ type == 'active' ? 'javascript:void(0);' : '/device' }}">Active</a>
					&nbsp;|&nbsp;
					<a class="{{ type == 'inactive' ? 'dark' : '' }}" href="{{ type == 'inactive' ? 'javascript:void(0);' : '/device/?type=0' }}">Deactivated</a>
					&nbsp;|&nbsp;
					<a href="/device/management">Add New</a>
				</div>
			</div>
		</div>
	</div>

	{% for device in devices %}
	<div class="row expanded">
		<div class="small-12 columns pb-0">
			<div class="panel device_container">
				<div class="row expanded icon-element" data-equalizer="{{ device['device_id'] }}">
					<div class="small-12 kill-padding columns" data-equalizer-watch="{{ device['device_id'] }}">
						<div class="element">
							{% if not device['datetime_disconnected'] and device['datetime_connected'] %}
								<i class="i-{{ device['device_type'] }} text-green text-4em pr-2"></i>
			                {% else %}
								<i class="i-{{ device['device_type'] }} text-mid-grey text-4em pr-2"></i>
			                {% endif %}
		              	</div>
					{% if type == 'active' %}
		                <div class="element">
							<p class="header">{{ device['user_device_name'] }}</p>
							{% if not device['datetime_disconnected'] and device['datetime_connected'] %}
			                    <p class="sub_header hide-for-small-only">Connected | {{ device['protected_data'] }} Data Protected All Time</p>
			                {% else %}
			                	{% if device['datetime_connected'] %}
			                    	<p class="sub_header hide-for-small-only">Last Connected: <?php echo date('F j Y g:i A', strtotime($device['datetime_disconnected'])) ?></p>
			                	{% else %}
			                    	<p class="sub_header hide-for-small-only">Not Connected</p>
			                	{% endif %}
			                {% endif %}
		                </div>

		                <div class="device_action">
		                	<a class="toggle_expand" href="javascript:void(0);"><i class="i-plus icon-xxl"></i></a>
		                </div>
					{% else %}
		                <div class="element">
							<p class="header">{{ device['user_device_name'] }}</p>
			                <p class="sub_header">{{ device['protected_data'] }} Data Protected All Time</p>
		                </div>

		                <div class="device_action">
			                {% if remaining_devices %}
		                	<a class="float-right" href="javascript:void(0);" data-toggle="device_action_{{ device['device_id'] }}"><i class="i-menu icon-xxl"></i></a>
			                <ul id="device_action_{{ device['device_id'] }}" class="dropdown-style medium bottom dropdown-pane" data-dropdown data-hover="true" data-hover-pane="true">
			                    <li><a href="/device/reactivate/{{ device['device_id'] }}">Reactivate Device</a></li>
			                    <!-- <li><a href="/device/forget/{{ device['device_id'] }}">Forget This Device</a></li> -->
			                </ul>
			                {% endif %}
		                </div>
					{% endif %}
					</div>
				</div>

				{% if type == 'active' %}
				<div class="row expanded">
					<div class="small-12 columns pad-l-6">
						<div class="device_info">
			                <div class="element pt-1">
								<h6><strong>Other Options</strong></h6>

			                    <p class="sub_header">
			                    	<a href="/device/rename/{{ device['device_id'] }}">Rename device</a>
			                    	&nbsp;|&nbsp;
			                    	<a class="text-mid-grey" href="/device/deactivate/{{ device['device_id'] }}">Deactivate device</a>
			                    </p>
			                </div>
						</div>
					</div>
				</div>
				{% endif %}
			</div>
		</div>
	</div>
	{% endfor %}

	<!-- Protection available for more devices -->
	<div class="row expanded">
		<div class="small-12 columns">
			<div class="panel device_container">
				<div class="row expanded icon-element" data-equalizer="protection_available">
				{% if remaining_devices %}
					<div class="small-12 columns kill-padding" data-equalizer-watch="protection_available">
						<div class="element width-6em text-center pr-2">
							<h3 class="text-light-grey kill-margin">{{ remaining_devices }}</h3>
							<p class="sub_header text-light-grey kill-margin">remaining</p>
						</div>
		                <div class="element" data-equalizer-watch="protection_available">
							<a class="button kill-margin" href="/device/management"><span class="text-white">Add a New Device</span></a>
		                </div>
					</div>
				{% else %}
					<a href="javascript:void(0);">
						<div class="large-1 medium-2 columns text-center" data-equalizer-watch="protection_available">
							<h2 class="text-light-grey"><i class="i-plus"></i></h2>
						</div>

						<div class="large-11 medium-10 columns" data-equalizer-watch="protection_available">
			                <div class="element">
								<p class="header">You've reached your device limit.</p>
				                <p class="sub_header">Purchase additional protection, or deactivate unused devices.</p>
			                </div>

			                <div class="device_action">
			                </div>
						</div>
					</a>
				{% endif %}
				</div>
			</div>
		</div>
	</div>
</div>
