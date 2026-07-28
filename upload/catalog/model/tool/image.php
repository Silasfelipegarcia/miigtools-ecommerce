<?php
namespace Opencart\Catalog\Model\Tool;
/**
 * Class Image
 *
 * Can be called using $this->load->model('tool/image');
 *
 * @package Opencart\Catalog\Model\Tool
 */
class Image extends \Opencart\System\Engine\Model {
	/**
	 * Resize
	 *
	 * @param string $filename
	 * @param int    $width
	 * @param int    $height
	 * @param string $default
	 *
	 * @throws \Exception
	 *
	 * @return string
	 */
	public function resize(string $filename, int $width, int $height, string $default = ''): string {
		$filename = html_entity_decode($filename, ENT_QUOTES, 'UTF-8');

		if (!is_file(DIR_IMAGE . $filename) || substr(str_replace('\\', '/', realpath(DIR_IMAGE . $filename)), 0, strlen(DIR_IMAGE)) != DIR_IMAGE) {
			return '';
		}

		$extension = pathinfo($filename, PATHINFO_EXTENSION);

		if ($extension === '') {
			return $this->config->get('config_url') . 'image/' . str_replace(' ', '%20', $filename);
		}

		$image_old = $filename;
		$dot = oc_strrpos($filename, '.');

		if ($dot === false) {
			return $this->config->get('config_url') . 'image/' . str_replace(' ', '%20', $filename);
		}

		$image_new = 'cache/' . oc_substr($filename, 0, $dot) . '-' . (int)$width . 'x' . (int)$height . '.' . $extension;

		if (!is_file(DIR_IMAGE . $image_new) || (filemtime(DIR_IMAGE . $image_old) > filemtime(DIR_IMAGE . $image_new))) {
			$previous = error_reporting(0);
			$info = getimagesize(DIR_IMAGE . $image_old);
			error_reporting($previous);

			if ($info === false) {
				return $this->config->get('config_url') . 'image/' . str_replace(' ', '%20', $image_old);
			}

			$width_orig = (int)($info[0] ?? 0);
			$height_orig = (int)($info[1] ?? 0);
			$image_type = (int)($info[2] ?? 0);

			if (!$width_orig || !$height_orig || !in_array($image_type, [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
				return $this->config->get('config_url') . 'image/' . str_replace(' ', '%20', $image_old);
			}

			$path = '';

			$directories = explode('/', dirname($image_new));

			foreach ($directories as $directory) {
				if (!$path) {
					$path = $directory;
				} else {
					$path = $path . '/' . $directory;
				}

				if (!is_dir(DIR_IMAGE . $path)) {
					@mkdir(DIR_IMAGE . $path, 0777);
				}
			}

			try {
				if ($width_orig != $width || $height_orig != $height) {
					$image = new \Opencart\System\Library\Image(DIR_IMAGE . $image_old);
					$image->resize($width, $height, $default);
					$image->save(DIR_IMAGE . $image_new);
				} else {
					copy(DIR_IMAGE . $image_old, DIR_IMAGE . $image_new);
				}
			} catch (\Throwable $e) {
				return $this->config->get('config_url') . 'image/' . str_replace(' ', '%20', $image_old);
			}
		}

		$image_new = str_replace(' ', '%20', $image_new);  // fix bug when attach image on email (gmail.com). it is automatically changing space from " " to +

		return $this->config->get('config_url') . 'image/' . $image_new;
	}
}
