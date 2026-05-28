<!-- welcome -->
<div style="margin: 20px 12px;">
	<div id="logout_reminder" style="padding-left:0; padding-right:0;display:[+show_logout_reminder+]">
		[+logout_reminder_msg+]
	</div>

	<div id="multiple_sessions" style="padding-left:0; padding-right:0;display:[+show_multiple_sessions+]">
		[+multiple_sessions_msg+]
	</div>

	<div class="tab-pane" id="welcomePane" style="border:0">
		<script type="text/javascript">
			tpPane = new WebFXTabPane(document.getElementById("welcomePane"), false);
		</script>

		<!-- home tab -->
		<div class="tab-page" id="tabhome" style="padding-left:0; padding-right:0;">
			[+OnManagerWelcomePrerender+]
			<h2 class="tab">[(site_name)]</h2>
			<script type="text/javascript">tpPane.addTabPage(document.getElementById("tabhome"));</script>
			<div class="sectionHeader">[%welcome_title%]</div>
			<div class="sectionBody">
				<table border="0" cellpadding="5">
					<tr>
						<td colspan="2">
							<h1>[(site_name)]</h1>
							[%welcome_title%]
						</td>
					</tr>
					<tr>
						<td width="100" align="right">
							<img src="media/style/[(manager_theme)]/images/misc/logo.png" alt="[%logo_slogan%]" />
							<br /><br />
						</td>
						<td valign="top">
							[+SecurityIcon+]
							[+WebUserIcon+]
							[+ModulesIcon+]
							[+ResourcesIcon+]
							[+BackupIcon+]
							<br style="clear:both" /><br />
							[+MessageInfo+]
						</td>
					</tr>
				</table>
			</div>
			[+OnManagerWelcomeHome+]
		</div>

		<!-- system check -->
		<div class="tab-page" id="tabcheck" style="display:[+config_display+]; padding-left:0; padding-right:0;">
			<h2 class="tab" style="display:[+config_display+]"><strong>[%settings_config%]</strong></h2>
			<script type="text/javascript"> if('[+config_display+]' == 'block') tpPane.addTabPage(document.getElementById("tabcheck"));</script>
			<div class="sectionHeader">[%configcheck_title%]</div>
			<div class="sectionBody">
				<i class="fa fa-times-circle"></i>
				[+config_check_results+]
			</div>
		</div>

		<!-- evo news -->
		<div class="tab-page" id="tabNews" style="padding-left:0; padding-right:0">
			<h2 class="tab">[%evo_release_title%]</h2>
			<script type="text/javascript">tpPane.addTabPage(document.getElementById("tabNews"));</script>
			<div class="sectionHeader">[%evo_release_title%]</div>
			<div class="sectionBody">
				[+evo_news_content+]
			</div>
		</div>

		<!-- extras releases -->
		<div class="tab-page" id="tabExtrasReleases" style="padding-left:0; padding-right:0">
			<h2 class="tab">[%extras_release_tab%]</h2>
			<script type="text/javascript">tpPane.addTabPage(document.getElementById("tabExtrasReleases"));</script>
			<div class="sectionHeader">[%extras_release_title%]</div>
			<div class="sectionBody">
				[+evo_extras_releases_content+]
			</div>
		</div>

		<!-- recent activities -->
		<div class="tab-page" id="tabAct" style="padding-left:0; padding-right:0">
			<h2 class="tab">[%recent_docs%]</h2>
			<script type="text/javascript">tpPane.addTabPage(document.getElementById("tabAct"));</script>
			<div class="sectionHeader">[%activity_title%]</div>
			<div class="sectionBody">
				[+RecentInfo+]
			</div>
		</div>

		<!-- user info -->
		<div class="tab-page" id="tabYour" style="padding-left:0; padding-right:0">
			<h2 class="tab">[%info%]</h2>
			<script type="text/javascript">tpPane.addTabPage(document.getElementById("tabYour"));</script>
			<div class="sectionHeader">[%yourinfo_title%]</div>
			<div class="sectionBody">
				[+UserInfo+]
			</div>
		</div>

		<!-- online info -->
		<div class="tab-page" id="tabOnline" style="padding-left:0; padding-right:0">
			<h2 class="tab">[%online%]</h2>
			<script type="text/javascript">tpPane.addTabPage(document.getElementById("tabOnline"));</script>
			<div class="sectionHeader">[%onlineusers_title%]</div>
			<div class="sectionBody">
				[+OnlineInfo+]
			</div>
		</div>
		[+OnManagerWelcomeRender+]
	</div>
</div>
