<?php

class UserContent {

	public $id;
	public $info;

	public function __construct($id) {
		$this->id = App::escape($id);
		$this->info = App::sql()->query_row("SELECT * FROM user_content WHERE id = '$this->id';");
	}

	/**
	 * Must be called from a valid URL context (browser only)
	 */
	public static function url_by_path($path) {
		return APP_URL.USER_CONTENT_URL.$path;
	}

	/**
	 * Must be called from a valid URL context (browser only)
	 */
	public function get_url() {
		return self::url_by_path($this->info->path);
	}

	public function get_path() {
		return USER_CONTENT_PATH.$this->info->path;
	}

	public function get_filename() {
		$chunks = explode('/', $this->info->path);
		return $chunks[count($chunks) - 1];
	}

	/**
	 * Must be called from a valid URL context (browser only)
	 */
	public function get_info() {
		return [
			'id'  => $this->id,
			'url' => $this->get_url()
		];
	}

	public function add_usage() {
		App::sql()->update("UPDATE user_content SET used = used + 1 WHERE id = '$this->id';");
		$this->info->used += 1;
	}

	public function remove_usage() {
		App::sql()->update("UPDATE user_content SET used = used - 1 WHERE id = '$this->id';");
		$this->info->used -= 1;
	}

	// Uploads files and returns UserContent objects
	// $field_name is the index in $_FILES array
	// $validations is an array, see FileUpload class

	public static function upload($field_name, $validations) {
		$ret = [
			'errors' => [],
			'files'  => []
		];

		// First, generate year/month directory
		$sub_dir = date('/Y/m');
		$dir = USER_CONTENT_PATH.$sub_dir;
		if(!file_exists($dir)) {
			if(!mkdir($dir, 0777, true)) {
				$ret['errors'][] = 'Upload error.';
				return $ret;
			}
		}

		// Upload file
		$fu = new FileUpload($_FILES[$field_name], $validations ?: []);

		$processFile = function($file) use (&$ret, $dir, $sub_dir) {
			$file->validate();
			if($error = $file->get_error(true)) {
				$ret['errors'][] = $error;
			} else {
				$ext = $file->get_info()->extension;
				$filename = App::new_uid(false, 32);
				if($file->put($dir, $filename.$ext)) {
					$user_id = App::user()->id ?: 0;
					$fullpath = $sub_dir.'/'.$filename.$ext;
					$new_id = App::sql()->insert("INSERT INTO user_content (user_id, path, used, datetime) VALUES ('$user_id', '$fullpath', 0, NOW());");
					if($new_id) {
						$ret['files'][] = new UserContent($new_id);
					} else {
						$ret['errors'][] = 'Database error.';
					}
				} else {
					$ret['errors'][] = $file->get_error(true);
				}
			}
		};

		$fu->each($processFile);
		return $ret;
	}

	// Uploads a single file by URL and returns UserContent object
	// Return value is the same as the upload() function's
	// $url is the URL of the file to download to the server
	// $validations is an array, see FileUpload class

	public static function upload_url($url, $validations) {
		$ret = [
			'errors' => [],
			'files'  => []
		];

		// Check if URL is valid
		if(strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
			$ret['errors'][] = 'Invalid URL.';
			return $ret;
		}

		// First, generate year/month directory
		$sub_dir = date('/Y/m');
		$dir = USER_CONTENT_PATH.$sub_dir;
		if(!file_exists($dir)) {
			if(!mkdir($dir, 0777, true)) {
				$ret['errors'][] = 'Upload error.';
				return $ret;
			}
		}

		// Download file from URL
		$temp_dir = trim(shell_exec('mktemp -d'));
		if(!$temp_dir || strpos($temp_dir, '/tmp/') !== 0) {
			$ret['errors'][] = 'Upload error.';
			return $ret;
		}

		$max_size = 20 * 1024 * 1024;
		shell_exec("cd \"$temp_dir\"; curl --max-filesize $max_size -O \"$url\"; cd -");
		$temp_dir_files = array_values(array_diff(scandir($temp_dir), ['..', '.']));
		if(!$temp_dir_files || count($temp_dir_files) === 0) {
			shell_exec("rm -rf $temp_dir");
			$ret['errors'][] = 'File not found.';
			return $ret;
		}
		$temp_file = $temp_dir_files[0];

		$temp_file_info = [
			'name' => $temp_file,
			'size' => filesize($temp_dir.'/'.$temp_file),
			'tmp_name' => $temp_dir.'/'.$temp_file,
			'type' => '',
			'error' => 0
		];

		// Process "uploaded" file
		$fu = new FileUpload($temp_file_info, $validations ?: []);

		$processFile = function($file) use (&$ret, $dir, $sub_dir) {
			$file->validate();
			if($error = $file->get_error(true)) {
				$ret['errors'][] = $error;
			} else {
				$ext = $file->get_info()->extension;
				$filename = App::new_uid(false, 32);
				if($file->put_any($dir, $filename.$ext)) {
					$user_id = App::user()->id ?: 0;
					$fullpath = $sub_dir.'/'.$filename.$ext;
					$new_id = App::sql()->insert("INSERT INTO user_content (user_id, path, used, datetime) VALUES ('$user_id', '$fullpath', 0, NOW());");
					if($new_id) {
						$ret['files'][] = new UserContent($new_id);
					} else {
						$ret['errors'][] = 'Database error.';
					}
				} else {
					$ret['errors'][] = $file->get_error(true);
				}
			}
		};

		$fu->each($processFile);

		// Delete temporary directory and all its contents
		shell_exec("rm -rf $temp_dir");

		return $ret;
	}

	// Uploads files and returns UserContent objects
	// $field_name is the index in $_FILES array

	public static function upload_smoothpower_update($field_name) {
		$ret = [
			'errors' => [],
			'files'  => []
		];

		// First, generate update directory
		$sub_dir = '/smoothpower_update';
		$dir = USER_CONTENT_PATH.$sub_dir;
		if(!file_exists($dir)) {
			if(!mkdir($dir, 0777, true)) {
				$ret['errors'][] = 'Upload error.';
				return $ret;
			}
		}

		// Upload file
		$fu = new FileUpload($_FILES[$field_name], []);

		$processFile = function($file) use (&$ret, $dir, $sub_dir) {
			$file->validate();

			// Extract file information and check if it's a valid smoothpower package
			$version = explode('-', explode('.', $file->name)[0])[1] ?: 0;
			$existing = App::sql()->query("SELECT id FROM smoothpower_update WHERE version = '$version';");
			$fullname = "smoothpower-$version.tar.gz";

			if ($file->name != $fullname) {
				$ret['errors'][] = 'Not a valid SmoothPower package.';
			} else if(!$version) {
				$ret['errors'][] = 'Invalid version number.';
			} else if($existing) {
				$ret['errors'][] = 'Version is already in the database.';
			} else if($error = $file->get_error(true)) {
				$ret['errors'][] = $error;
			} else {
				$filename = $file->name;
				if($file->put($dir, $filename)) {
					$user_id = App::user()->id ?: 0;
					$fullpath = $sub_dir.'/'.$filename;
					$new_id = App::insert('smoothpower_update', [
						'version' => $version,
						'channel' => 'test',
						'datetime' => App::now(),
						'rollback' => 0
					]);
					if($new_id) {
						$rec = App::select('smoothpower_update', $new_id);
						if($rec) {
							$ret['files'][] = $rec;
						} else {
							$ret['errors'][] = 'Insert error.';
						}
					} else {
						$ret['errors'][] = 'Database error.';
					}
				} else {
					$ret['errors'][] = $file->get_error(true);
				}
			}
		};

		$fu->each($processFile);
		return $ret;
	}

	// Proportionally resize the image to fit into the passed size
	public function resize_image($width, $height) {
		$file = $this->get_path();
		smart_resize_image($file, null, $width, $height, true, $file);
	}

	// Proportionally shrink the image to fit into the passed size
	// If image is already smaller, it won't be changed
	public function shrink_image($width, $height) {
		$file = $this->get_path();
		$info = getimagesize($file);
		list($width_old, $height_old) = $info;

		// Don't do anything if image is already smaller or same size
		if($width_old <= $width && $height_old <= $height) return;

		smart_resize_image($file, null, $width, $height, true, $file);
	}

}
