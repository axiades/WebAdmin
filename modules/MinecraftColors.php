<?php
/*
	Copyright (c) 2019 Anders G. Jørgensen - http://spirit55555.dk

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

class MinecraftColors {
	const REGEX = '/(?:§|&amp;)([0-9a-fklmnor])/i';

	const START_TAG_INLINE_STYLED = '<span style="%s">';
	const START_TAG_WITH_CLASS = '<span class="%s">';
	const CLOSE_TAG = '</span>';

	const CSS_COLOR  = 'color: #';
	const EMPTY_TAGS = '/<[^\/>]*>([\s]?)*<\/[^>]*>/';
	const LINE_BREAK = '<br />';

	static private $colors = array(
		'0' => '000000', //Black
		'1' => '0000AA', //Dark Blue
		'2' => '00AA00', //Dark Green
		'3' => '00AAAA', //Dark Aqua
		'4' => 'AA0000', //Dark Red
		'5' => 'AA00AA', //Dark Purple
		'6' => 'FFAA00', //Gold
		'7' => 'AAAAAA', //Gray
		'8' => '555555', //Dark Gray
		'9' => '5555FF', //Blue
		'a' => '55FF55', //Green
		'b' => '55FFFF', //Aqua
		'c' => 'FF5555', //Red
		'd' => 'FF55FF', //Light Purple
		'e' => 'FFFF55', //Yellow
		'f' => 'FFFFFF'  //White
	);

	static private $formatting = array(
		'k' => '',                               //Obfuscated
		'l' => 'font-weight: bold;',             //Bold
		'm' => 'text-decoration: line-through;', //Strikethrough
		'n' => 'text-decoration: underline;',    //Underline
		'o' => 'font-style: italic;',            //Italic
		'r' => ''                                //Reset
	);

	static private $css_classnames = array(
		'0' => 'black',
		'1' => 'dark-blue',
		'2' => 'dark-green',
		'3' => 'dark-aqua',
		'4' => 'dark-red',
		'5' => 'dark-purple',
		'6' => 'gold',
		'7' => 'gray',
		'8' => 'dark-gray',
		'9' => 'blue',
		'a' => 'green',
		'b' => 'aqua',
		'c' => 'red',
		'd' => 'light-purple',
		'e' => 'yellow',
		'f' => 'white',
		'k' => 'obfuscated',
		'l' => 'bold',
		'm' => 'line-strikethrough',
		'n' => 'underline',
		'o' => 'italic'
	);

	static private function UFT8Encode($text) {
		//Encode the text in UTF-8, but only if it's not already.
		if (mb_detect_encoding($text) != 'UTF-8')
			$text = utf8_encode($text);

		return $text;
	}

	static public function clean($text) {
		$text = self::UFT8Encode($text);
		$text = htmlspecialchars($text);

		return preg_replace(self::REGEX, '', $text);
	}

	static public function convertToMOTD($text, $sign = '\u00A7') {
		$text = self::UFT8Encode($text);
		$text = str_replace("&", "&amp;", $text);
		$text = preg_replace(self::REGEX, $sign.'${1}', $text);
		$text = str_replace("\n", '\n', $text);
		$text = str_replace("&amp;", "&", $text);

		return $text;
	}

	static public function convertToHTML($text, $line_break_element = false, $css_classes = false, $css_prefix = 'minecraft-formatted--') {
		$text = self::UFT8Encode($text);
		$text = htmlspecialchars($text);

		preg_match_all(self::REGEX, $text, $offsets);

		$colors      = $offsets[0]; //This is what we are going to replace with HTML.
		$color_codes = $offsets[1]; //This is the color numbers/characters only.

		//No colors? Just return the text.
		if (empty($colors))
			return $text;

		$open_tags = 0;

		foreach ($colors as $index => $color) {
			$color_code = strtolower($color_codes[$index]);

			$html = '';

			$is_reset = $color_code === 'r';
			$is_color = isset(self::$colors[$color_code]);

			if ($is_reset || $is_color) {
				// New colors or the reset char: reset all other colors and formatting.
				if ($open_tags != 0) {
					$html = str_repeat(self::CLOSE_TAG, $open_tags);
					$open_tags = 0;
				}
			}

			if ($css_classes) {
				if (!$is_reset) {
					$cssClassname = $css_prefix.self::$css_classnames[$color_code];
					$html .= sprintf(self::START_TAG_WITH_CLASS, $cssClassname);
					$open_tags++;
				}
			}

			else {
				if ($is_color) {
					$html .= sprintf(self::START_TAG_INLINE_STYLED, self::CSS_COLOR.self::$colors[$color_code]);
					$open_tags++;
				}

				else if ($color_code === 'k')
					$html = '';

				else if (!$is_reset) {
					$html .= sprintf(self::START_TAG_INLINE_STYLED, self::$formatting[$color_code]);
					$open_tags++;
				}
			}

			//Replace the color with the HTML code. We use preg_replace because of the limit parameter.
			$text = preg_replace('/'.$color.'/', $html, $text, 1);
		}

		//Still open tags? Close them!
		if ($open_tags != 0)
			$text = $text.str_repeat(self::CLOSE_TAG, $open_tags);

		//Replace \n with <br />
		if ($line_break_element) {
			$text = str_replace("\n", self::LINE_BREAK, $text);
			$text = str_replace('\n', self::LINE_BREAK, $text);
		}

		//Return the text without empty HTML tags. Only to clean up bad color formatting from the user.
		return preg_replace(self::EMPTY_TAGS, '', $text);
	}
}
?>
