<?php
/**
 * Substitutor is a MediaWiki Extension that does one-time string substitution.
 *
 * Common "magic words" in MediaWiki are replaced dynamically in MediaWiki.
 * You would not want this dynamic behaviour with timestamps or unique strings / numbers / IDs,
 * since they should be fixed after they were created.
 * This provides useful if you want to create unique names and URL's for "red links",
 * or simply timestamp sites / changes.
 *
 * For more info see http://mediawiki.org/wiki/Extension:Substitutor
 *
 * @file
 * @ingroup Extensions
 * @package MediaWiki
 *
 * @links https://github.com/Fannon/Substitutor/blob/master/README.md Documentation
 * @links https://www.mediawiki.org/wiki/Extension_talk:Substitutor Support
 * @links https://github.com/Fannon/Substitutor/issues Bug tracker
 * @links https://github.com/Fannon/Substitutor Source code
 *
 * @author Simon Heimler (Fannon), 2015
 * @license http://opensource.org/licenses/mit-license.php The MIT License (MIT)
 */


//////////////////////////////////////////
// VARIABLES                            //
//////////////////////////////////////////

$dir = dirname(__FILE__);
$dirbasename = basename($dir);


//////////////////////////////////////////
// CREDITS                              //
//////////////////////////////////////////

$wgExtensionCredits['other'][] = array(
    'path' => __FILE__,
    'name' => 'Substitutor',
    'author' => array('Simon Heimler'),
    'version' => '1.2.0',
    'url' => 'https://www.mediawiki.org/wiki/Extension:Substitutor',
    'descriptionmsg' => 'substitutor-desc',
    'license-name' => 'MIT'
);


//////////////////////////////////////////
// LOAD FILES                           //
//////////////////////////////////////////

// Register hooks
$wgHooks['PageContentSave'][] = 'onPageContentSave';
$wgHooks['ParserFirstCallInit'][] = 'onParserSetup';


//////////////////////////////////////////
// HOOK CALLBACKS                       //
//////////////////////////////////////////

/**
 * Hook: After a wiki page is saved,
 * look for the <substitute> tag, parse its content and replace the complete tag with it.
 */
function onPageContentSave(&$wikiPage, &$user, &$content, &$summary, $isMinor, $isWatch, $section, &$flags, &$status)
{

    global $wgOut;

    $re = "/<substitute>(.*?)<\\/substitute>/s";

    $text = $content->getContentHandler()->serializeContent($content);
    $title = $wikiPage->getTitle();
    $namespace = $title->getNamespace();

    // Do not execute substitutions for the Form: Namespace
    // This makes it possible to use substitutions in form field's default values
    if ($namespace === 106) {
        return true;
    }

    $newText = $text;

    preg_match_all($re, $newText, $matches);

    if ($matches[1]) {
        foreach ($matches[1] as $match) {
            $replacement = $wgOut->parseInline($match, false);

            $completeMatch = '<substitute>' . $match . '</substitute>';

            // Only replace the first instance of the match within the wikitext
            // http://stackoverflow.com/a/1252710/776425
            $pos = strpos($newText, $completeMatch);
            if ($pos !== false) {
                $newText = substr_replace($newText, $replacement, $pos, strlen($completeMatch));
            }
        }
    }

    if ($newText != $text) {
        $content = $content->getContentHandler()->unserializeContent($newText);
    }

    return true;
}

function onParserSetup( Parser $parser ) {
    $parser->setHook( 'substitute', 'renderSubstituteTag' );
}

// Return the tag exactly as it was, including the tag itself
// The actual substitution will be made on the "onPageContentSave" hook
// This is just to prevent mediawiki from dynamically parsing the content within the tag
function renderSubstituteTag( $input, array $args, Parser $parser, PPFrame $frame ) {
    return '<substitute>' . $input . '</substitute>';
}