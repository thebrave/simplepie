<?php

// SPDX-FileCopyrightText: 2024 Jean Berniolles
// SPDX-License-Identifier: BSD-3-Clause

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class OpmlParseAllTest extends TestCase
{
    public static function parseXmlFile(string $filePath): ?SimpleXMLElement
    {
        if (!file_exists($filePath)) {
            print ("File not found: $filePath");
            return null;
        }

        $xmlContent = file_get_contents($filePath);
        if ($xmlContent === false) {
            print ("$filePath is not an XML file");
            return null;
        }

        return simplexml_load_string($xmlContent);
    }

    public static function parseOpmlFile(string $filePath): ?array
    {
        $xml = OpmlParseAllTest::parseXmlFile($filePath);
        if ($xml === null) {
            return null;
        }

        $result = [];
        for ($xml->rewind(); $xml->valid(); $xml->next()) {
            foreach ($xml->getChildren() as $name => $data) {
                OpmlParseAllTest::parseOutline($name, $data, $result, 1);
            }
        }

        return $result;
    }

    public static function parseOutline(string $name, SimpleXMLElement &$xml, array &$result, int $level): void
    {
        print (str_repeat('>', $level) . " " . $level . " " . $name . ">> ");

        if (!isset($xml['text'])) {
            print (str_repeat('>', $level) . " " . $name . ": ");
            if ($xml->count() > 0) {
                print ("no text attribute " . $xml->count() . " childs\n");
                foreach ($xml->children() as $sub_name => $sub_data) {
                    print ("godown\n");
                    OpmlParseAllTest::parseOutline($sub_name, $sub_data, $result, $level + 1);
                }
            } else {
                print ("no text attribute, no children\n");
            }
            return;
        }

        print (str_repeat('>', $level) . " " . $name . "=" . $xml['text'] . ": ");
        if (isset($xml['xmlUrl'])) {
            $obj = [];
            $obj[] = (string) $xml['text'];
            $obj[] = (string) $xml['type'];
            $obj[] = (string) $xml['xmlUrl'];
            $obj[] = (string) $xml['htmlUrl'];
            $result[] = $obj;
            print ((string) $xml['xmlUrl'] . "\n");
        } else if ($xml->count() > 0) {
            print ("no xmlUrl " . $xml->count() . " childs\n");
            foreach ($xml->children() as $sub_name => $sub_data) {
                OpmlParseAllTest::parseOutline($sub_name, $sub_data, $result, $level + 1);
            }
        } else {
            print ("no xmlUrl, no children\n");
        }
    }

    public static function rssFeedProvider(): array
    {
        $array = OpmlParseAllTest::parseOpmlFile("tests/data/feeds.opml.xml");

        return $array;
    }

    #[DataProvider('rssFeedProvider')]
    public function testAdd(string &$text, string &$type, string &$xmlUrl, string &$htmlUrl): void
    {
        $feed = new SimplePie();
        $feed->enable_cache(false);
        $feed->set_feed_url($xmlUrl);

        $this->assertTrue($feed->init());

        unset($feed);
    }
}