<div class="panels_container shield-container">
    <div class="row expanded network-settings">
        <div class="medium-12 columns dash-page">
            <div class="dash-block panel">
                <h4>Your Wireless Network</h4>

		        <div class="row expanded">
		            <div class="small-12 medium-4 columns">
		                <p class="title">Network Name</p>
		            </div>

		            <div class="small-12 medium-4 columns end secure_wireless_name">
		                <div class="static-field">{{ settings.secure_wireless_name }}</div>

		                <div class="edit-field hide">
		                    <input class="dash-form" type="text" placeholder="Network Name" value="{{ settings.secure_wireless_name }}" data-original="{{ settings.secure_wireless_name }}" data-field="secure_wireless_name" required>
		                </div>
		            </div>
		        </div>

		        <div class="row expanded">
		            <div class="small-12 medium-4 columns">
		                <p class="title">Password</p>
		            </div>

		            <div class="small-10 medium-4 columns password_container password_hidden secure_wireless_password">
		                <div class="static-field">
		                	<label data-original="{{ settings.secure_wireless_password }}">********</label>
		                	<a class="toggle_password"><i class="i-invisible"></i></a>
		                </div>

		                <div class="edit-field hide">
		                    <input class="dash-form" type="password" placeholder="New Password" data-original="{{ settings.secure_wireless_password }}" data-field="secure_wireless_password" required>
		                    <div class="field-info" data-info="Updating network settings..."></div>
		                    <a class="button" href="javascript:void(0);">Save Changes</a>
		                </div>
		            </div>

		            <div class="small-2 medium-4 columns">
		                <a class="edit" href="javascript:void(0);">Edit</a>
		            </div>
		        </div>
            </div>
        </div>
    </div>
</div>