<?php
require_once("security.php");
if (isset($_REQUEST["profile"])) {
    $profile = strip_tags($_REQUEST["profile"]);
} else {
    if (isset($_SESSION["last_runned_profile"])) {
        $profile = $_SESSION["last_runned_profile"];
    }
}
if ($profile == "") {
    $profile = "default";
}
$profilename = ($profile != "default" ? $profile : "Default");
?>

<script type="text/javascript">
		var profile="<?php echo $profile ?>";
	</script>
<div class="panel panel-default" id="profile_action">
	<div class="panel-heading">
		<h3 class="panel-title">Configure Current Profile (<?php echo $profilename?>)
			<?php
			$eplconf = new EnabledPlugins_Config($profile);
			$eplconf->load();
			$conf_ok = $eplconf->hasSection("PLUGINS_DATASOURCES");
			?>

			<span class="saveinfo pull-right<?php if (!$conf_ok):?> log_warning <?php endif; ?>" id="profileconf_msg">
				<?php if ($conf_ok): ?>
					Saved:<?php echo $eplconf->getLastSaved("%c")?>
				<?php else: ?>
					<?php echo $profilename?> Profile Config not saved yet
				<?php endif; ?>
			</span>
		</h3>
	</div>
	<div class="panel-body">
		<form action="magmi_chooseprofile.php" method="POST" class="form-horizontal" id="chooseprofile">
			<h4>Profile to configure</h4>
			<div class="form-group">
				<label class="col-sm-2 control-label">Current Magmi Profile:</label>
				<div class="col-sm-10">
					<select class="form-control" name="profile" onchange="$('chooseprofile').submit()">
						<option <?php if (null==$profile): ?> selected="selected" <?php endif; ?> value="default">
							Default
						</option>
						<?php foreach ($profilelist as $profname): ?>
							<option <?php if ($profname==$profile): ?> selected="selected" <?php endif; ?>
								value="<?php echo $profname?>">
								<?php echo $profname?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label">Copy Selected Profile to:</label>
				<div class="col-sm-10">
					<input class="form-control" type="text" name="newprofile">
				</div>
			</div>
			<input type="submit" class="btn btn-default" value="Copy Profile &amp; switch">
			<?php
				require_once("magmi_pluginhelper.php");
				$order = array("datasources","general","itemprocessors");
				$plugins = Magmi_PluginHelper::getInstance('main')->getPluginClasses($order);
				$pcats = array();
				foreach ($plugins as $k => $pclasslist) {
				    foreach ($pclasslist as $pclass) {
				        // invoke static method, using call_user_func (5.2 compat mode)
				        $pcat = call_user_func(array($pclass, "getCategory"));
				        if (!isset($pcats[$pcat])) {
				            $pcats[$pcat] = array();
				        }
				        $pcats[$pcat][] = $pclass;
				    }
				}
			?>
		</form>
	</div>
</div>
<div id="profile_cfg">
	<form action="" method="POST" id="saveprofile_form">
		<input type="hidden" name="profile" id="curprofile" value="<?php echo $profile?>">

	<?php foreach ($order as $k): // foreah plugin loop ?>
	<input type="hidden" id="plc_<?php echo strtoupper($k)?>"
			value="<?php echo implode(",", $eplconf->getEnabledPluginClasses($k))?>"
			name="PLUGINS_<?php echo strtoupper($k)?>:classes">
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title"><?php echo ucfirst($k)?></h3>
			</div>
			<div class="panel-body">
			<?php if ($k == "datasources"):  // if datasoures plugins ?>
				<?php $pinf=$plugins[$k]; ?>
				<?php if (count($pinf)>0): ?>
					<div class="pluginselect form-group">
						<select name="PLUGINS_DATASOURCES:class" class="pl_<?php echo $k?> form-control">
							<?php $sinst = null; ?>

							<?php foreach ($pinf as $pclass):
									$pinst = Magmi_PluginHelper::getInstance($profile)->createInstance($k, $pclass);
									if ($sinst == null) {
										$sinst = $pinst;
									}
									$pinfo = $pinst->getPluginInfo();
									if ($eplconf->isPluginEnabled($k, $pclass)) {
										$sinst = $pinst;
									}
							?>
							<option value="<?php echo $pclass?>" <?php  if ($sinst==$pinst): ?>	selected="selected" <?php endif; ?>>
								<?php echo $pinfo["name"]." v".$pinfo["version"]?>
							</option>
							<?php endforeach; ?>
						</select>
					</div>
					<?php if (isset($pinfo["url"])): ?>
						<div class="plugindoc">
							<a href="<?php echo $pinfo["url"]?>" target="magmi_doc">documentation</a>
						</div>
					<?php endif; ?>
					<div class="pluginconfpanel selected">
						<?php echo $sinst->getOptionsPanel()->getHtml(); ?>
					</div>
					<?php else: ?>
					<?php $conf_ok = 0; ?>
					Magmi needs a datasource plugin, please install one
				<?php endif; ?>
			<?php else: // end datasources plugings, on to general and itemprocessors ?>
				<?php foreach ($pcats as $pcat => $pclasslist): // plugin category loop ?>
				<?php $pinf = $plugins[$k]; ?>
					<?php if(!array_intersect($pinf, $pclasslist)) continue; ?>
					<div class="grid_12 group">
						<h4><?php echo $pcat?></h4>

					<?php foreach ($pinf as $pclass): // general and itemprocessors loop ?>
						<?php if (!in_array($pclass, $pclasslist)) continue; ?>
							<ul>
								<?php
									$pinst = Magmi_PluginHelper::getInstance($profile)->createInstance($k, $pclass);
									$pinfo = $pinst->getPluginInfo();
									$info = $pinst->getShortDescription();
									$plrunnable = $pinst->isRunnable();
									$enabled = $eplconf->isPluginEnabled($k, $pclass)
								?>
								<li>
								<div class="pluginselect">
									<?php if ($plrunnable[0]): ?>
										<input type="checkbox" class="pl_<?php echo $k?>" name="<?php echo $pclass?>"
										<?php if ($eplconf->isPluginEnabled($k, $pclass)): ?> checked="checked" <?php endif; ?>>
									<?php else: ?>
										<input type="checkbox" class="pl_<?php echo $k?>" name="<?php echo $pclass?>" disabled="disabled">
									<?php endif; ?>
									<span class="pluginname <?php if (isset($pinfo['sponsorinfo'])):?> sponsored <?php endif; ?>">
										<?php echo $pinfo["name"]." v".$pinfo["version"]; ?>
									</span>
								</div>
								<div class="plugininfo">
									<span>info</span>
									<div class="plugininfohover">
										<div class="plugindata">
											<ul>
											<?php $sp = isset($pinfo["sponsorinfo"]); ?>
											<?php foreach ($pinfo as $pik => $piv): ?>
												<li <?php if (isset($sp)): ?> class='sponsored' <?php endif; ?>>
												<?php if ($pik == "url"): ?>
													<span><?php echo $pik?></span>:
													<span><a href="<?php echo $piv?>" target="_blank">Wiki entry</a></span>
												<?php elseif($pik == "sponsorinfo"): ?>
													<span class="sponsor">Sponsored By</span>:
													<span>
													<?php if (isset($piv['url'])): ?>
														<a href="<?php echo $piv['url']?>" target="_blank"><?php echo $piv["name"]; ?></a>
													<?php else: echo $piv["name"]; ?>
													<?php endif;?>
													</span>
												<?php else: ?>
													<span><?php echo $pik?></span>:<span><?php echo $piv ?></span>
												<?php endif; ?>
												</li>
											<?php endforeach; ?>
											</ul>
											<div class="minidoc">
												<?php echo $info?>
											</div>
										</div>
										<?php if (!$plrunnable[0]): ?>
											<div class="error">
												<pre><?php echo $plrunnable[1]?></pre>
											</div>
										<?php endif; ?>
									</div>
								</div>
								<div class="pluginconf" <?php if (!$enabled): ?> style="display: none" <?php endif; ?>>
									<span><a href="javascript:void(0)">configure</a></span>
								</div>
								<?php if (isset($pinfo["url"])): ?>
									<div class="plugindoc">
										<a href="<?php echo $pinfo["url"]?>" target="magmi_doc">documentation</a>
									</div>
								<?php endif; ?>

								<div class="pluginconfpanel">
									<?php if ($enabled) echo $pinst->getOptionsPanel()->getHtml(); ?>
								</div>
								</li>
							</ul>
						<?php endforeach;  //general and itemprocessors loop ?>
					</div>
				<?php endforeach; // plugin category loop ?>
			<?php endif; ?>
			</div>
		</div>
	<?php endforeach; // plugin ?>
</form>
	<div class="grid_12">
		<div style="float: right">
			<a id="saveprofile" class="actionbutton" href="javascript:void(0)"
				<?php if (!$conf_ok) {
    ?> disabled="disabled" <?php 
}?>>Save Profile (<?php echo $profilename?>)</a>
		</div>
	</div>
</div>

<div id="paramchanged" style="display: none">
	<div class="subtitle">
		<h3>Parameters changed</h3>
	</div>

	<div class="changedesc">
		<b>You changed parameters without saving profile , would you like to:</b>
	</div>

	<ul>
		<li><input type="radio" name="paramcr" value="saveprof">Save chosen Profile (<?php echo $profilename ?>) with current parameters
	</li>
		<li><input type="radio" name="paramcr" value="applyp"
			checked="checked">Apply current parameters as profile override
			without saving</li>
		<li><input type="radio" name="paramcr" value="useold">Discard changes &amp; apply last saved <?php echo $profilename ?> profile values
	</li>
	</ul>
	<div class="actionbuttons">
		<a class="actionbutton"
			href="javascript:handleRunChoice('paramcr',comparelastsaved());"
			id="paramchangeok">Run with selected option</a> <a
			class="actionbutton" href="javascript:cancelimport();"
			id="paramchangecancel">Cancel Run</a>
	</div>
</div>

<div id="pluginschanged" style="display: none">
	<div class="subtitle">
		<h3>Plugin selection changed</h3>
	</div>
	<div class="changedesc">
		<b>You changed selected plugins without saving profile , would you
			like to:</b>
	</div>

	<ul>
		<li><input type="radio" name="plugselcr" value="saveprof"
			checked="checked">Save chosen Profile (<?php echo $profilename ?>) with current parameters
	</li>
		<li><input type="radio" name="plugselcr" value="useold">Discard changes &amp; apply  last saved <?php echo $profilename ?> profile values
	</li>
	</ul>
	<div class="actionbuttons">
		<a class="actionbutton"
			href="javascript:handleRunChoice('plugselcr',comparelastsaved());"
			id="plchangeok">Run with selected option</a> <a class="actionbutton"
			href="javascript:cancelimport();" id="plchangecancel">Cancel Run</a>
	</div>
</div>

<script type="text/javascript">

window.lastsaved={};

handleRunChoice=function(radioname,changeinfo)
{
	var changed=changeinfo.changed;
	var sval=$$('input:checked[type="radio"][name="'+radioname+'"]').pluck('value');
	if(sval=='saveprof')
	{
		saveProfile(1,function(){$('runmagmi').submit();});
	}
	if(sval=='useold')
	{
		$('runmagmi').submit();
	}
	if(sval=='applyp')
	{
		changed.each(function(it){
			$('runmagmi').insert({bottom:'<input type="hidden" name="'+it.key+'" value="'+it.value+'">'});
		});
		$('runmagmi').submit();
	}
}

cancelimport=function()
{
 $('overlay').hide();
}

updatelastsaved=function()
{
 gatherclasses(['DATASOURCES','GENERAL','ITEMPROCESSORS']);
 window.lastsaved=$H($('saveprofile_form').serialize(true));
};

comparelastsaved=function()
{
 gatherclasses(['DATASOURCES','GENERAL','ITEMPROCESSORS']);
 var curprofvals=$H($('saveprofile_form').serialize(true));
 var changeinfo={changed:false,target:''};
 var out="";
 var diff={};
 changeinfo.target='paramchanged';
 curprofvals.each(function(kv)
 {
	 var lastval=window.lastsaved.get(kv.key);
 	if(kv.value!=lastval)
 	{
		diff[kv.key]=kv.value;
		if(kv.key.substr(0,8)=="PLUGINS_")
		{
			changeinfo.target='pluginschanged';
		}
	}
 });

changeinfo.changed=$H(diff);
if(changeinfo.changed.size()==0)
{
	changeinfo.changed=false;
}
 return changeinfo;
};

addclass=function(it,o)
{
	if(it.checked){
		this.arr.push(it.name);
	}
};

gatherclasses=function(tlist)
{
	tlist.each(function(t,o){
		var context={arr:[]};
		$$(".pl_"+t.toLowerCase()).each(addclass,context);
		var target=$("plc_"+t);
		target.value=context.arr.join(",");
	});
};

initConfigureLink=function(maincont)
{
 var cfgdiv=maincont.select('.pluginconf');
 if(cfgdiv.length>0)
 {
 	cfgdiv=cfgdiv[0];
 	var confpanel=maincont.select('.pluginconfpanel');
	 confpanel=confpanel[0]
	cfgdiv.stopObserving('click');
 	cfgdiv.observe('click',function(ev){
 	 	confpanel.toggleClassName('selected');
 		 confpanel.select('.ifield').each(function(it){
 			it.select('.fieldhelp').each(function(fh){
 				fh.observe('click',function(ev){
 					it.select('.fieldsyntax').each(function(el){el.toggle();})
 						});
 				});
 			});
 	 	});

 }
};
showConfLink=function(maincont)
{
	var cfgdiv=maincont.select('.pluginconf');
	if(cfgdiv.length>0)
	 {

	cfgdiv=cfgdiv[0];
	cfgdiv.show();
	 }

};

loadConfigPanel=function(container,profile,plclass,pltype)
{
 new Ajax.Updater({success:container},'ajax_pluginconf.php',
	{parameters:{
		profile:profile,
        plugintype:pltype,
		pluginclass:plclass},
		evalScripts:true,
		onComplete:
	 	function(){
	 		showConfLink($(container.parentNode));
	 		initConfigureLink($(container.parentNode));
	 	}});
};

removeConfigPanel=function(container)
{
var cfgdiv=$(container.parentNode).select('.pluginconf');
cfgdiv=cfgdiv[0];
cfgdiv.stopObserving('click');
 cfgdiv.hide();
 container.removeClassName('selected');
 container.update('');
};


initAjaxConf=function(profile)
{
	//foreach plugin selection
	$$('.pluginselect').each(function(pls)
	{
		var del=pls.firstDescendant();
		var evname=(del.tagName=="SELECT"?'change':'click');

		//check the click
		del.observe(evname,function(ev)
		{
			var el=Event.element(ev);
			var plclass=(el.tagName=="SELECT")?el.value:el.name;
			var elclasses=el.classNames();
			var pltype="";
			elclasses.each(function(it){if(it.substr(0,3)=="pl_"){pltype=it.substr(3);}});
			var doload=(el.tagName=="SELECT")?true:el.checked;
			var targets=$(pls.parentNode).select(".pluginconfpanel");
			var container=targets[0];
			if(doload)
			{
				loadConfigPanel(container,profile,plclass,pltype);
			}
			else
			{
				removeConfigPanel(container);
			}
		});
	});
};

initDefaultPanels=function()
{
	$$('.pluginselect').each(function(it){initConfigureLink($(it.parentNode));});
	updatelastsaved();
};

saveProfile=function(confok,onsuccess)
{
	gatherclasses(['DATASOURCES','GENERAL','ITEMPROCESSORS']);
  	updatelastsaved();
	new Ajax.Updater('profileconf_msg',
			 "magmi_saveprofile.php",
			 {parameters:$('saveprofile_form').serialize('true'),
			  onSuccess:function(){
			  if(confok)
              {
				 onsuccess();
			  }
			  else
			  {
			  	$('profileconf_msg').show();
			  }}
	  		});

};

initAjaxConf('<?php echo $profile?>');
initDefaultPanels();


$('saveprofile').observe('click',function()
								{
									saveProfile(<?php echo $conf_ok?1:0 ?>,function(){$('chooseprofile').submit();});
									});

$('runmagmi').observe('submit',function(ev){

	var ls=comparelastsaved();
	if(ls.changed!==false)
	{
		 $('overlaycontent').update($(ls.target));
		 $$('#overlaycontent > div').each(function(el){el.show()});
		 $('overlay').show();
		 ev.stop();
	}
	});
	</script>
