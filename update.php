<?php
	// This runs the main updater.  Requires PHP, 7-Zip, and git on the path and repo commit access.  That last part you, of course, don't have.
	// (C) 2016 CubicleSoft.  All Rights Reserved.

	if (!isset($_SERVER["argc"]) || !$_SERVER["argc"])
	{
		echo "This file is intended to be run from the command-line.";

		exit();
	}

	$rootpath = str_replace("\\", "/", dirname(__FILE__));

	require_once $rootpath . "/functions.php";
	require_once $rootpath . "/support/cli.php";
	require_once $rootpath . "/support/web_browser.php";
	require_once $rootpath . "/support/simple_html_dom.php";

	$srcpath = $rootpath . "/src";
	$destpath = $rootpath . "/dest";

	if (!is_dir($srcpath))  mkdir($srcpath);
	if (!is_dir($destpath))  mkdir($destpath);

	@ini_set("memory_limit", "1500M");

	// Download the latest versions of Git Portable edition.
	$web = new WebBrowser();
	$result = $web->Process("https://git-scm.com/download/win");
	if (!$result["success"])  CLI::DisplayError("An error occurred while retrieving content.", $result);
	if ($result["response"]["code"] != 200)  CLI::DisplayError("Expected 200 server response.", $result);

	$html = new simple_html_dom();
	$html->load($result["body"]);

	$verinfo = @json_decode(file_get_contents($rootpath . "/ver.dat"));
	if (!is_array($verinfo))  $verinfo = array();

	function Download_Callback($response, $data, $opts)
	{
		if ($response["code"] == 200)
		{
			$size = ftell($opts);
			fwrite($opts, $data);

			if ($size % 1000000 > ($size + strlen($data)) % 1000000)  echo ".";
		}

		return true;
	}

	$urls = array();
	$rows = $html->find('a[href]');
	foreach ($rows as $row)
	{
		if (preg_match('/PortableGit-(.*?)-(\d+)-bit/i', $row->href, $match))
		{
			$ver = $match[1];
			$bits = $match[2] . "-bit";
			if (!isset($verinfo[$bits]) || $verinfo[$bits] !== $ver)
			{
				$url = HTTP::ConvertRelativeToAbsoluteURL($result["url"], (string)$row->href);

				echo "Downloading '" . $url . "'...";

				$web2 = clone $web;
				$fp = fopen($srcpath . "/temp.7z", "wb");
				$options = array(
					"read_body_callback" => "Download_Callback",
					"read_body_callback_opts" => $fp
				);
				$result2 = $web2->Process($url, $options);
				fclose($fp);

				echo "\n";

				if (!$result2["success"])  CLI::DisplayError("An error occurred while retrieving content.", $result2);
				if ($result2["response"]["code"] != 200)  CLI::DisplayError("Expected 200 server response.", $result2);

				echo "Extracting files...\n";
				@mkdir($srcpath . "/" . $bits);
				DeleteDirectory($srcpath . "/" . $bits);

				system("7z x -y " . escapeshellarg("-o" . $srcpath . "/" . $bits) . " " . escapeshellarg($srcpath . "/temp.7z"));

				echo "Copying files to destination...\n";
				if (file_exists($srcpath . "/" . $bits . "/usr/bin/ssh.exe"))
				{
					// Clear target directory of EXEs and DLLs.
					@mkdir($destpath . "/" . $bits);
					$dir = opendir($destpath . "/" . $bits);
					if ($dir)
					{
						while (($file = readdir($dir)) !== false)
						{
							if ($file !== "." && $file !== ".." && (substr($file, -4) === ".exe" || substr($file, -4) === ".dll"))  @unlink($destpath . "/" . $bits . "/" . $file);
						}

						closedir($dir);
					}

					// Copy ssh.exe.
					echo $srcpath . "/" . $bits . "/usr/bin/ssh.exe\n";
					copy($srcpath . "/" . $bits . "/usr/bin/ssh.exe", $destpath . "/" . $bits . "/ssh.exe");

					$data = file_get_contents($destpath . "/" . $bits . "/ssh.exe");

					// Copy all referenced DLLs.
					$path = $srcpath . "/" . $bits . "/usr/bin";
					$dlls = array();
					do
					{
						$found = false;

						$dir = opendir($path);
						if ($dir)
						{
							while (($file = readdir($dir)) !== false)
							{
								if ($file !== "." && $file !== ".." && substr($file, -4) === ".dll")
								{
									if (!isset($dlls[$file]) && stripos($data, $file) !== false)
									{
										echo $path . "/" . $file . "\n";
										copy($path . "/" . $file, $destpath . "/" . $bits . "/" . $file);

										$data .= file_get_contents($destpath . "/" . $bits . "/" . $file);

										$dlls[$file] = true;
										$found = true;
									}
								}
							}

							closedir($dir);
						}
					} while ($found);
				}

				CommitRepo($destpath . "/" . $bits);

				$verinfo[$bits] = $ver;
			}
		}
	}

	file_put_contents($rootpath . "/ver.dat", json_encode($verinfo));
?>