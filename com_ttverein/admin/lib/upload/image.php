<?php
require('class.upload.php');

class Image {
	var $error = null;
	
	function resizeImage($imageOrginal, $imageResize, $newSize) {
		$imageOrginal = Image::putPathSlashBefore($imageOrginal);
		$imageOrginal = Image::putPathSlashBefore($imageResize);
		
		if(is_file(JPATH_ROOT . $imageOrginal)) {
			$handle = new Upload(JPATH_ROOT . $imageOrginal);
			
			$handle->image_resize            = true;
	        $handle->image_ratio_y           = true;
	        if($handle->image_x >= $handle->image_y)
	        	$handle->image_x             = $newSize;
	        else
	        	$handle->image_y             = $newSize;
	        $handle->Process(JPATH_ROOT . $imageResize);
	        if (!$handle->processed)
				return false;	
			return true;
		}
		
		return false;		
	}	
	
	function deleteImages($images) {
		foreach($images as $image) {
			$image_orginal = Image::convertToOsPath($image->image_orginal);
			$image_resize = Image::convertToOsPath($image->image_resize);
			$image_thumb = Image::convertToOsPath($image->image_thumb);
			@unlink(JPATH_ROOT . $image_orginal);
			@unlink(JPATH_ROOT . $image_resize);
			@unlink(JPATH_ROOT . $image_thumb);
		}
	}
	
	function saveImage($image, $newName, $path, $image_size, $thumb_size) {
		$newName = Image::filterString($newName);
		$path = Image::convertToOsPath($path);
		$path = Image::putPathSlash($path);
		
		$handle = new upload($image);
		
	    if ($handle->uploaded) {

			/*
			 * Orginal Foto
			 */
			$tmpPath = JPATH_ROOT . '/' . "tmp" . '/';
	    	$handle->process($tmpPath);
			
			if (!$handle->processed) {
				JError::raiseWarning( 552, "Processing uploaded file failed: " . $handle->error);
				JError::raiseWarning( 552, $handle->log);
			
				$this->error = $handle->error;
				return null;
			}
				
			//Wenn Ordner nicht vorhanden, dann erstellen.
			if(!file_exists(JPATH_ROOT . $path)) {
				if(!$handle->rmkdir(JPATH_ROOT . $path)) {
					$this->error = "Kann Verzeichnis " . JPATH_ROOT . $path . " nicht erstellen";
					return null;
				}
			}
			
			$filePath = $path . $newName. '.jpg';
			//Altes Bild löschen
			if(is_file(JPATH_ROOT . $filePath))
				unlink(JPATH_ROOT . $filePath);
			rename($handle->file_dst_pathname, JPATH_ROOT . $filePath);
			$fileNames['image_orginal'] = Image::convertToUnixPath($filePath);
	
			/*
			 * Detail Foto
			 */
			$handle->image_resize            = true;
	       	if($handle->image_src_x >= $handle->image_src_y) {
	       		$handle->image_ratio_y           = true;
	       		$handle->image_x             = $image_size;
	       	} else {
	        	$handle->image_y             = $image_size;
	        	$handle->image_ratio_x           = true;
	       	}
	        $handle->process(JPATH_ROOT . $path . ".." . '/');
	       	        
	        if (!$handle->processed) {
				$this->error = "Kann Foto nicht verkleinern";
				return null;
	        }
	       	$filePath = $path . $newName. '_resize.jpg';
	       //Altes Bild löschen
	       	if(is_file(JPATH_ROOT . $filePath))
	       		unlink(JPATH_ROOT . $filePath);
	       	rename($handle->file_dst_pathname, JPATH_ROOT . $filePath);
	        $fileNames['image_resize'] = Image::convertToUnixPath($filePath);

			/*
			 * Vorschaubild
			 */
	        $handle->image_resize            = true;
	        if($handle->image_src_x >= $handle->image_src_y) {
	        	$handle->image_x             = $thumb_size;
	        	$handle->image_ratio_y           = true;
	        } else {
	        	$handle->image_y             = $thumb_size;
	        	$handle->image_ratio_x           = true;
	        }
	        $handle->process(JPATH_ROOT . $path . ".."  . '/');
	        if (!$handle->processed) {
				$this->error = "Kann Foto nicht verkleinern";
				return null;
	        }
	        $filePath = $path . $newName. '_thumb.jpg';
	        //Altes Bild löschen
	       	if(is_file(JPATH_ROOT . $filePath))
	        	unlink(JPATH_ROOT . $filePath);
	        rename($handle->file_dst_pathname, JPATH_ROOT . $filePath);
			$fileNames['image_thumb'] = Image::convertToUnixPath($filePath);

	       	$handle-> Clean();
			return $fileNames;
	    }
	    
	    $this->error = "Datei nicht angekommen.";
		return null;
	}
	
	/*
	 * Umlaute und Leerzeichen werden umgewandelt 
	 */
	function filterString($string) {
		return str_replace (array("ä", "ö", "ü", "ß", "Ä", "Ö", "Ü", " "),
								array("ae", "oe", "ue", "ss", "Ae", "Oe", "Ue", "_"),
								$string);
	}
	
	/*
	 * Wandelt einen Unix Pfad in einen Pfad für das aktuelle Betriebsystem
	 */
	function convertToOsPath($path) {
		if('/' != "/")
			$path = str_replace("/", "\\", $path);
		return $path;
	}
	
	/*
	 * Konvertiert einen Pfad in einen Unix Pfad
	 */
	function convertToUnixPath($path) {
		return str_replace("\\", "/", $path);
	}
	
	function putPathSlash($path) {
		$path = Image::putPathSlashBefore($path);
		return Image::putPathSlashAfter($path);
	}
	
	function putPathSlashAfter($path) {
		//Ist am Ende kein / wird es angehangen
		if(substr($path, strlen($path)-1, 1) != '/')
			$path .= '/';
		return $path;
	}
	function putPathSlashBefore($path) {
		//Ist am Anfang kein / wird es vorrangestellt
		if(substr($path, 0, 1) != '/')
			$path = '/' . $path;
		return $path;
	}
}
?>
