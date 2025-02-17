<!doctype html>

<?php
	$WORKING_DIR=dirname(__FILE__);
	$config = parse_ini_file($WORKING_DIR . "/config.cfg", false);
	$constants = parse_ini_file($WORKING_DIR . "/constants.sh", false);

	$theme = $config["conf_THEME"];
	$background = $config["conf_BACKGROUND_IMAGE"] == ""?"":"background='" . $constants["const_MEDIA_DIR"] . '/' . $constants["const_BACKGROUND_IMAGES_DIR"] . "/" . $config["conf_BACKGROUND_IMAGE"] . "'";

	include("sub-popup.php");
?>

<html lang="en" data-theme="<?php echo $theme; ?>">
<!-- Author: Dmitri Popov, dmpop@linux.com
         License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

<head>
	<?php include "${WORKING_DIR}/sub-standards-header-loader.php"; ?>
	<script src="js/logmonitor.js"></script>
	<script src="js/refresh_site.js"></script>
</head>

<body onload="refreshLogMonitor(); refresh_site()" <?php echo $background; ?>>
	<?php include "${WORKING_DIR}/sub-standards-body-loader.php"; ?>
	<!-- Suppress form re-submit prompt on refresh -->
	<script>
		if (window.history.replaceState) {
			window.history.replaceState(null, null, window.location.href);
		}
	</script>

	<?php include "${WORKING_DIR}/sub-menu.php"; ?>

	<h1 class="text-center" style="margin-bottom: 1em; letter-spacing: 3px;"><?php echo L::sysinfo_sysinfo; ?></h1>

	<div class="card">
		<h3><?php echo L::sysinfo_system; ?></h3>
		<?php
			$model					= shell_exec("sudo python3 ${WORKING_DIR}/lib_system.py get_pi_model");

			$temp					= shell_exec('cat /sys/class/thermal/thermal_zone*/temp');
			$temp					= round((float) $temp / 1000, 1);

			$cpuusage				= 100 - (float) shell_exec("vmstat | tail -1 | awk '{print $15}'");

			$mem_ram_frac			= shell_exec("free | grep Mem | awk '{print $3/$2 * 100.0}'");
			$mem_ram_all			= shell_exec("free | grep Mem | awk '{print $2 / 1024}'");
			$mem_ram				= round((float) $mem_ram_frac, 1) . " % * " . round((float) $mem_ram_all) . " MB";

			$mem_swap_frac			= shell_exec("free | grep Swap | awk '{print $3/$2 * 100.0}'");
			$mem_swap_all			= shell_exec("free | grep Swap | awk '{print $2 / 1024}'");
			$mem_swap				= round($mem_swap_frac, 1) . " % * " . round($mem_swap_all) . " MB";

			$abnormal_conditions	= shell_exec("sudo python3 ${WORKING_DIR}/lib_system.py get_abnormal_system_conditions");

			echo '<table>';

			echo "<tr><td width='30%'>" . L::sysinfo_model . ": </td><td><strong>" . $model . "</strong></td></tr>";

			if (isset($temp) && is_numeric($temp)) {
				echo "<tr><td>" . L::sysinfo_temp . ": </td><td><strong>" . $temp . "°C</strong></td></tr>";
			}

			if (isset($cpuusage) && is_numeric($cpuusage)) {
				echo "<tr><td>" . L::sysinfo_cpuload . ": </td><td><strong>" . $cpuusage . "%</strong></td></tr>";
			}

			echo "<tr><td>" . L::sysinfo_memory_ram . ": </td><td><strong>" . $mem_ram . "</strong></td></tr>";

			echo "<tr><td>" . L::sysinfo_memory_swap . ": </td><td><strong>" . $mem_swap . "</strong></td></tr>";

			echo "<tr><td>" . L::sysinfo_conditions . ": </td><td><strong>" . $abnormal_conditions . "</strong></td></tr>";

			echo '</table>';
		?>
	</div>

	<div class="card">
		<h3><?php echo L::sysinfo_devices; ?></h3>
			<?php
			echo '<pre>';
			passthru("sudo lsblk");
			echo '</pre>';
			?>
	</div>

	<div class="card">
		<h3><?php echo L::sysinfo_diskspace; ?></h3>
			<?php
				echo '<pre>';
				passthru("sudo df -H");
				echo '</pre>';
			?>
	</div>

	<div class="card">
		<h3><?php echo L::sysinfo_cameras; ?></h3>
			<?php
				echo '<pre>';


					exec("sudo gphoto2 --auto-detect",$DEVICES);
					if (count($DEVICES)>2) {
						echo "<ol>";

							$FirstColumnLength	= strpos($DEVICES[0],'Port');

							$LineNumber	= 0;
							foreach ($DEVICES as $DEVICE) {
								$LineNumber	+= 1;

									if ($LineNumber > 2) {
										$DEVICE	= substr($DEVICE,0,$FirstColumnLength);
										$DEVICE	= trim($DEVICE);

										echo "<li>";

											echo "<h3>" . L::sysinfo_camera_identifier ."</h3>";
											echo "<ul>";

												echo "<li>$DEVICE</li>";

											echo "</ul>";

											echo '<h4>' . L::sysinfo_camera_model.'</h4>';
											exec("sudo gphoto2 --camera '$DEVICE' --summary | grep 'Model' | cut -d: -f2 | tr -d ' '",$SUMMARY);
											if (count($SUMMARY)) {
												echo "<ul>";

													$MODEL	= mb_ereg_replace("([^a-zA-Z0-9-_\.])", '_', $SUMMARY[0]);

													echo "<li>$MODEL</li>";

												echo "</ul>";
											}
											else
											{
												echo "-";
											}

											echo '<h4>' . L::sysinfo_camera_serial.'</h4>';
											unset($SUMMARY);
											exec("sudo gphoto2 --camera '$DEVICE' --summary | grep 'Serial Number' | cut -d: -f2 | tr -d ' '",$SUMMARY);
											if (count($SUMMARY)) {
												echo "<ul>";

													$SERIAL	= mb_ereg_replace("([^a-zA-Z0-9-_\.])", '_', $SUMMARY[0]);
													$SERIAL	= ltrim($SERIAL, "0");

													echo "<li>$SERIAL</li>";

												echo "</ul>";
											}
											else
											{
												echo "-";
											}

											echo '<h4>' . L::sysinfo_camera_storages.'</h4>';
											exec("sudo gphoto2 --camera '$DEVICE' --storage-info | grep 'basedir' | cut -d= -f2 | tr -d ' '",$STORAGES);
											if (count($STORAGES)) {
												echo "<ul>";
													foreach ($STORAGES as $STORAGE) {

														echo "<li>$STORAGE</li>";
													}
												echo "</ul>";
											}
											else
											{
												echo "-";
											}

										echo "</li>";


									}



							}

						echo "</ol>";
					}
					else
					{
						echo "-";
					}

				echo '</pre>';
			?>
	</div>

	<div class="text-center"><button onClick="history.go(0)" role="button"><?php echo (L::sysinfo_refresh_button); ?></button></div>

	<?php include "sub-logmonitor.php"; ?>

	<?php include "sub-footer.php"; ?>

</body>

</html>
