<?php

namespace CSV;

//CSV data, when on the array of strings form, are of course utf8-encoded,
//as working with non utf8 strings doesn't make sense for years.
//People willing to use CSV to store in a CSV non string data, but raw
//bytes array (ie string vs char[]) are wrong and should not do it.

//Microsoft Excel is so badly implemented that there is no constant
//CSV file format it can open. Indeed, CSV delimiters are localized... what
//a good idea for compatibility. Thus this implementation use the delimiter
//used by default by an english configured Excel (';'). The same way, Microsoft
//didn't think that using a universal encoding is a good idea, even if unicode
//as become the de-facto standard for years... Once again, this encoding is localized
//to make sure using Excel is a compatibility nightmare. Thus this implementation use
//the default ISO-8859-1 encoding.

class CSV{
	const DELIMITER=';';
	const ENCLOSURE='"';

	static public function toString(array $fields,$delimiter=CSV::DELIMITER,$enclosure=CSV::ENCLOSURE){
		return static::write(static::getTemporaryFileStream(),$fields,$delimiter,$enclosure);
	}

	static public function toFile($filePath,array $fields,$delimiter=CSV::DELIMITER,$enclosure=CSV::ENCLOSURE){
		return static::write(static::getFileStream($filePath,'w+'),$fields,$delimiter,$enclosure);
	}

	static public function fromString($rawFields,$delimiter=CSV::DELIMITER,$enclosure=CSV::ENCLOSURE){
		$fileStream=static::getTemporaryFileStream();

		if(fwrite($fileStream,$rawFields)===false){
			throw new \RuntimeException('Failed to write raw fields to temporary file');
		}

		if(rewind($fileStream)===false){
			throw new \RuntimeException('Failed to rewind temporary file');
		}

		return static::read($fileStream,$delimiter,$enclosure);
	}

	static public function fromFile($filePath,$delimiter=CSV::DELIMITER,$enclosure=CSV::ENCLOSURE){
		return static::read(static::getFileStream($filePath,'r'),$delimiter,$enclosure);
	}

	static protected function write($fileStream,array $fields,$delimiter,$enclosure){
		foreach($fields as $fieldsLine){
			foreach($fieldsLine as &$field){
				$field=utf8_decode((string)$field);
			}

			if(fputcsv($fileStream,$fieldsLine,$delimiter,$enclosure)===false){
				throw new \RuntimeException('Failed to write CSV data');
			}
		}

		return static::readAndCloseFileStream($fileStream);
	}

	static protected function read($fileStream,$delimiter,$enclosure){
		$fields=[];

		while(true){
			$fieldsLine=fgetcsv($fileStream,0,$delimiter,$enclosure);

			if($fieldsLine===false){
				if(!feof($fileStream)){
					throw new \RuntimeException('Failed to read CSV data');
				}else{
					break;
				}
			}

			if(is_array($fieldsLine)){
				foreach($fieldsLine as &$field){
					$field=utf8_encode($field);
				}
			}

			$fields[]=$fieldsLine;
		}

		fclose($fileStream);

		return $fields;
	}

	static protected function getTemporaryFileStream(){
		return static::getFileStream('php://temp','r+');
	}

	static protected function getFileStream($filePath,$mode){
		if(($fileStream=fopen($filePath,$mode))===false){
			throw new \RuntimeException(sprintf('Failed to open file "%s"',$filePath));
		}

		return $fileStream;
	}

	static protected function readAndCloseFileStream($fileStream){
		if(($content=stream_get_contents($fileStream,-1,0))===false){
			throw new \RuntimeException(sprintf('Failed to read CSV data from "%s"',$filePath));
		}

		fclose($fileStream);

		return $content;
	}
}
