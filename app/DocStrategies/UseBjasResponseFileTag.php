<?php

namespace App\DocStrategies;

use Knuckles\Camel\Extraction\ExtractedEndpointData;
use Knuckles\Scribe\Extracting\RouteDocBlocker;
use Knuckles\Scribe\Extracting\Shared\ResponseFileTools;
use Knuckles\Scribe\Extracting\Strategies\Strategy;
use Knuckles\Scribe\Tools\AnnotationParser;
use Knuckles\Scribe\Tools\Utils;
use Mpociot\Reflection\DocBlock\Tag;

/**
 * Get a response from from a file in the docblock ( @bjasResponseFileTag ).
 */
class UseBjasResponseFileTag extends Strategy
{
    public function __invoke(ExtractedEndpointData $endpointData, array $routeRules = []): ?array
    {
        $docBlocks = RouteDocBlocker::getDocBlocksFromRoute($endpointData->route);
        return $this->getFileResponses($docBlocks['method']->getTags());
    }

    /**
     * Get the response from the file if available.
     *
     * @param Tag[] $tags
     *
     * @return array|null
     */
    public function getFileResponses(array $tags): ?array
    {
        $responseFileTags = Utils::filterDocBlockTags($tags, 'bjasresponsefile');

        if (empty($responseFileTags)) return null;

        $responses = array_map(function (Tag $responseFileTag) {
            preg_match('/^(\d{3})?\s*(.*?)({.*})?$/', $responseFileTag->getContent(), $result);
            [$_, $status, $mainContent] = $result;
            $json = $result[3] ?? null;

            ['fields' => $fields, 'content' => $filePath] = AnnotationParser::parseIntoContentAndFields($mainContent, ['status', 'scenario']);

            $status = $fields['status'] ?: ($status ?: 200);
            $description = $fields['scenario'] ?: "";

            $content = ResponseFileTools::getResponseContents($filePath, $json);

            $filePath = base_path($filePath);

            if (!file_exists($filePath)) {
                throw new \InvalidArgumentException("@bjasResponseFile {$filePath} does not exist");
            }

            $content = $this->getContent($filePath);
            $content = ['data' => $content];

            if ($json) {
                $json = str_replace("'", '"', $json);
                $json = json_decode($json, true);

                if (isset($json['exclude']) && is_array($json['exclude'])) {
                    foreach ($json['exclude'] as $exclude) {
                        if (isset($content['data'][$exclude])) {
                            unset($content['data'][$exclude]);
                        }
                    }
                }

                if (isset($json['list']) && $json['list']) {
                    $content['data'] = [$content['data']];
                } elseif (isset($json['pagination']) && $json['pagination']) {
                    $content['data'] = [$content['data']];
                    $content['meta'] = [
                        'last_page' => 1,
                        'per_page' => 20,
                        'page' => 1,
                        'total' => 1
                    ];
                }
            }

            return [
                'content' => json_encode($content),
                'status' => (int)$status,
                'description' => $description,
            ];
        }, $responseFileTags);

        return $responses;
    }

    private function getContent($filePath)
    {
        $content = json_decode(file_get_contents($filePath, true), true);

        foreach ($content as $key => $value) {
            if (is_array($value)) {
                continue;
            }

            if (strpos($value, '@includeArray') === 0) {
                preg_match('/@includeArray (.*)/', $value, $includeFilePath);
                $includeFilePath[1] = base_path($includeFilePath[1]);

                if (isset($includeFilePath[1]) && file_exists($includeFilePath[1])) {
                    $content[$key] = [$this->getContent($includeFilePath[1])];
                }
            } elseif (strpos($value, '@include') === 0) {
                preg_match('/@include (.*)/', $value, $includeFilePath);
                $includeFilePath[1] = base_path($includeFilePath[1]);

                if (isset($includeFilePath[1]) && file_exists($includeFilePath[1])) {
                    $content[$key] = $this->getContent($includeFilePath[1]);
                }
            }
        }

        return $content;
    }
}
