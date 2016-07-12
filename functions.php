<?php
	// Baseline shared functions for other automated repos to utilize.
	// (C) 2016 CubicleSoft.  All Rights Reserved.

	function DeleteDirectory($path)
	{
		if (substr($path, -1) == "/")  $path = substr($path, 0, -1);

		$dir = opendir($path);
		if ($dir)
		{
			while (($file = readdir($dir)) !== false)
			{
				if ($file != "." && $file != "..")
				{
					if (is_link($path . "/" . $file) || is_file($path . "/" . $file))
					{
						chmod($path . "/" . $file, 0666);
						unlink($path . "/" . $file);
					}
					else
					{
						DeleteDirectory($path . "/" . $file);
						rmdir($path . "/" . $file);
					}
				}
			}

			closedir($dir);
		}
	}

	function GitRepoChanged($rootpath)
	{
		chdir($rootpath);
		ob_start();
		system("git status");
		$data = ob_get_contents();
		ob_end_flush();

		return (stripos($data, "Changes not staged for commit:") !== false || stripos($data, "Untracked files:") !== false);
	}

	function CommitRepo($rootpath)
	{
		if (GitRepoChanged($rootpath))
		{
			// Commit all the things.
			system("git add -A");
			system("git commit -m \"Updated.\"");
			system("git push origin master");
		}
	}
?>