<!-- welcome -->
<div style="margin: 20px 12px;">
	<script type="text/javascript" src="media/script/tabpane.js"></script>
	<div class="tab-pane" id="welcomePane" style="border:0">
    <script type="text/javascript">
        tpPane = new WebFXTabPane(document.getElementById( "welcomePane" ),false);
    </script>

		<!-- home tab -->
		<div class="tab-page" id="tabhome" style="padding-left:0; padding-right:0;">
[+OnManagerWelcomePrerender+]			
			<h2 class="tab">[+site_name+]</h2>
			<script type="text/javascript">tpPane.addTabPage( document.getElementById( "tabhome" ) );</script>
			<div class="sectionHeader">[+welcome_title+]</div>
			<div class="sectionBody">
                <table border="0" cellpadding="5">
                  <tr>
                    <td colspan="2">
                        <h1 style="margin:0">[+site_name+]</h1>
                        [+welcome_title+]
                    </td>
                  </tr>
                  <tr>
                    <td width="100" align="right">
                        <img src='media/style/[+theme+]/images/misc/logo.png' alt='[+logo_slogan+]' />
                        <br /><br />
                    </td>
                    <td valign="top">
[+OnManagerWelcomeHome+]
                        <span class="wm_button" style="border:0">[+SecurityIcon+]</span>
                        <span class="wm_button" style="border:0">[+WebUserIcon+]</span>
                        <span class="wm_button" style="border:0">[+ModulesIcon+]</span>
                        <span class="wm_button" style="border:0">[+ResourcesIcon+]</span>
                        <span class="wm_button" style="border:0">[+BackupIcon+]</span>
                        <br style="clear:both" /><br />
                        [+MessageInfo+]
                    </td>
                  </tr>
                </table>
			</div>
		</div>
		
		<!-- system check -->
		<div class="tab-page" id="tabcheck" style="display:[+config_display+]; padding-left:0; padding-right:0;">
			<h2 class="tab" style="display:[+config_display+]"><strong style="color:#ffeb00;">[+settings_config+]</strong></h2>
			<script type="text/javascript"> if('[+config_display+]'=='block') tpPane.addTabPage( document.getElementById( "tabcheck" ) );</script>
			<div class="sectionHeader">[+configcheck_title+]</div>
			<div class="sectionBody">
				<img src="media/style/[+theme+]/images/icons/error.png" />
				[+config_check_results+]
			</div>
		</div>
		
		<!-- modx news -->
		<div class="tab-page" id="tabNews" style="padding-left:0; padding-right:0">
			<h2 class="tab">[+modx_news+]</h2>
			<script type="text/javascript">tpPane.addTabPage( document.getElementById( "tabNews" ) );</script>
			<div class="sectionHeader">[+modx_news_title+]</div><div class="sectionBody">
				[+modx_news_content+]
			</div>
		</div>	

		<!-- security notices -->
		<div class="tab-page" id="tabSecurityNotices" style="padding-left:0; padding-right:0">
			<h2 class="tab">[+modx_security_notices+]</h2>
			<script type="text/javascript">tpPane.addTabPage( document.getElementById( "tabSecurityNotices" ) );</script>
			<div class="sectionHeader">[+modx_security_notices_title+]</div><div class="sectionBody">
				[+modx_security_notices_content+]
			</div>
		</div>

		<!-- recent activities -->
		<div class="tab-page" id="tabAct" style="padding-left:0; padding-right:0">
			<h2 class="tab">[+recent_docs+]</h2>
			<script type="text/javascript">tpPane.addTabPage( document.getElementById( "tabAct" ) );</script>
			<div class="sectionHeader">[+activity_title+]</div><div class="sectionBody">
				[+RecentInfo+]
			</div>
		</div>

		<!-- user info -->
		<div class="tab-page" id="tabYour" style="padding-left:0; padding-right:0">
			<h2 class="tab">[+info+]</h2>
			<script type="text/javascript">tpPane.addTabPage( document.getElementById( "tabYour" ) );</script>
			<div class="sectionHeader">[+yourinfo_title+]</div><div class="sectionBody">
				[+UserInfo+]
			</div>
		</div>

		<!-- online info -->
		<div class="tab-page" id="tabOnline" style="padding-left:0; padding-right:0">
			<h2 class="tab">[+online+]</h2>
			<script type="text/javascript">tpPane.addTabPage( document.getElementById( "tabOnline" ) );</script>
			<div class="sectionHeader">[+onlineusers_title+]</div><div class="sectionBody">
				[+OnlineInfo+]
			</div>
		</div>
[+OnManagerWelcomeRender+]
	</div>
</div>