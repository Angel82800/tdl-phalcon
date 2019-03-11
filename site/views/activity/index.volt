{% if session.get('ftu') is empty or session.get('ftu')['activity'] is empty or session.get('ftu')['activity']['todyl_info'] is empty %}
<div class="ftu_notification pt-2 text-center ajax-hidden">
	<div class="medium-8 medium-centered">
		<img src="/img/dashboard/ftu-activity.png" />
		<h5 class="text-mid-grey pt-1">
			Todyl Protection is extremely proactive. If we notice even the smallest potential problem with your internet traffic, we'll block the problem and analyze it.<br />
			Todyl blocks thousands of potential threats for all our users daily.
		</h5>
		<div class="pt-1">
			<a id="btn_pass_ftu" class="button">Got It, Thanks</a>
		</div>
	</div>
</div>
{% endif %}

<div class="panels_container activity-container ajax-panels">
	<div id="activity_stats" class="row expanded" data-equalizer="stats-row" data-equalize-on="large">
		<div class="medium-4 columns" data-equalizer-watch="stats-row">
			<div class="panel">
				<h2 class="stat-value"></h2>
				<h5 class="text-mid-grey">Connected in the last 30 days</h5>
			</div>
		</div>

		<div class="medium-4 columns" data-equalizer-watch="stats-row">
			<div class="panel">
				<h2 class="stat-value"></h2>
				<h5 class="text-mid-grey">Potential threats blocked</h5>
			</div>
		</div>

		<div class="medium-4 columns" data-equalizer-watch="stats-row">
			<div class="panel">
				<h2 class="stat-value"></h2>
				<h5 class="text-mid-grey">Devices currently connected</h5>
			</div>
		</div>
	</div>

	<div class="row expanded">
		<div class="medium-12 columns">
			<div class="panel kill-padding">
				<div class="filter_container clearfix">
					<div class="filter medium-2 small-6 end columns">
						<select id="filter_activity_period">
							<option value="1" selected="selected">Today</option>
							<option value="7">Last 7 Days</option>
							<option value="30">Last 30 Days</option>
						</select>
					</div>
					<!--
					<div class="filter medium-3 small-12 columns">
						<input type="text" id="activity_filter" placeholder="Search This List">
					</div>
					-->
				</div>

				<div class="table_container">
					<table id="activity_list" class="hover unstriped">
						<thead>
							<tr>
								<th width="15%">Time</th>
								<th width="10%">Severity</th>
								<th width="15%">Location</th>
								<th width="15%">Alert</th>
								<th width="30%">Description</th>
								<th width="15%">Result</th>
							</tr>
						</thead>
						<tbody></tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
