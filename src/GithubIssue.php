<?php


namespace bouiboui\Tissue;

use Github\Api\Issue;
use Github\Api\Search;
use Github\Client as GithubClient;
use Github\Exception\InvalidArgumentException;
use Github\Exception\MissingArgumentException;


/**
 * Class GithubIssue
 * @package bouiboui\Tissue
 */
class GithubIssue
{
    const READABLE_TITLE_LENGTH = 50; // My arbitrary personal belief, feel free to disagree

    private $number;
    private $url;
    private $title;
    private $body;

    /**
     * GithubIssue constructor.
     * Internally formats the Github issue title and message
     * @param string $message
     * @param int $code
     * @param int $severity
     * @param string $path
     * @param int $lineno
     * @param string $trace
     */
    public function __construct($message = null, $code = null, $severity = null, $path = null, $lineno = null, $trace = null)
    {
        // Default message
        if (null === $message) {
            $message = 'An error occured.';
        }

        // Format the title under 50 characters
        $this->title = GithubIssue::formatTitle($path, $lineno, $message);

        // Only display a two-parent-directories-deep path, for readability
        $shortPath = GithubIssue::formatPath($path);

        $bodyContents = [];

        // Head table (Code and Severity)
        if (null !== $code || null !== $severity) {
            $bodyContents[] = GithubIssue::formatTable($code, $severity);
        }

        // $path:$line
        if (null !== $path) {
            $pathText = '**Path**' . PHP_EOL . $shortPath;
            if (null !== $lineno) {
                $pathText .= ':**' . $lineno . '**';
            }
            $bodyContents[] = $pathText;
        }
        if (null !== $message) {
            $bodyContents[] = '**Message**' . PHP_EOL . $message;
        }
        if (null !== $trace) {
            $bodyContents[] = '**Stack trace**' . PHP_EOL . '```' . PHP_EOL . $trace . PHP_EOL . '```';
        }

        // Format the body
        $this->body = GithubIssue::formatBody($bodyContents);
    }

    /**
     * Formats the issue's title
     * @param $path
     * @param $lineno
     * @param $message
     * @return string
     */
    private static function formatTitle($path, $lineno, $message)
    {
        $title = '';
        // [basename($path):$line] $shortMessage
        if (null !== $path) {
            $title .= '[' . basename($path);
            if (null !== $lineno) {
                $title .= ':' . $lineno;
            }
            $title .= '] ';
        }
        $shortMessage = $message;
        if (mb_strlen($message) >= GithubIssue::READABLE_TITLE_LENGTH) {
            $shortMessage = mb_substr($message, 0, GithubIssue::READABLE_TITLE_LENGTH - 1) . '…';
        }
        $title .= $shortMessage;
        return $title;
    }

    /**
     * Formats the issue's path
     * @param $path
     * @return string
     */
    private static function formatPath($path)
    {
        $dirs = explode(DIRECTORY_SEPARATOR, $path);
        return '..' . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, array_slice($dirs, count($dirs) - 3));
    }

    /**
     * Formats the issue's table
     * @param $code
     * @param $severity
     * @return string
     */
    private static function formatTable($code, $severity)
    {
        $displayCode = null !== $code;
        $displaySeverity = null !== $severity;
        $tableTitle = '';
        $tableContents = '';
        $tableDivider = '---';
        if ($displayCode && $displaySeverity) {
            $tableTitle = 'Code | Severity';
            $tableDivider = '--- | ---';
            $tableContents = $code . ' | ' . $severity;
        } else if ($displayCode) {
            $tableTitle = 'Code';
            $tableContents = $code;
        } else if ($displaySeverity) {
            $tableTitle = 'Severity';
            $tableContents = $severity;
        }
        return '| ' . $tableTitle . ' |' . PHP_EOL . '| ' . $tableDivider . ' |' . PHP_EOL . '| ' . $tableContents . ' |';
    }

    /**
     * Formats the issue's body
     * @param array $bodyContents
     * @return string
     */
    private static function formatBody(array $bodyContents)
    {
        return implode(PHP_EOL . PHP_EOL, $bodyContents);
    }

    /**
     * Actually creates the issue on Github, returns an array with the issue's number and URL.
     * @param $yourUsername
     * @param $yourPassword
     * @param $targetRepoAuthor
     * @param $targetRepoName
     * @return array
     * @throws InvalidArgumentException
     * @throws MissingArgumentException
     * @throws ErrorException
     */
    public function commit($yourUsername, $yourPassword, $targetRepoAuthor, $targetRepoName)
    {
        $client = new GithubClient();
        $client->authenticate($yourUsername, $yourPassword, GithubClient::AUTH_HTTP_PASSWORD);

        /** @var Search $searchApi */
        $searchApi = $client->api('search');
        /** @var Issue $issueApi */
        $issueApi = $client->api('issue');

        // Check existing issues to avoid duplicates
        $duplicates = $searchApi->issues($this->title . ' in:title state:open label:bug repo:' . $targetRepoAuthor . '/' . $targetRepoName);

        if ((int)$duplicates['total_count'] > 0) {
            return ['duplicate' => true];
        }

        // Create the issue and fetch the issue's info
        $issueInfo = $issueApi->create(
            $targetRepoAuthor,
            $targetRepoName, [
                'title' => $this->title,
                'body' => $this->body
            ]
        );

        if (!array_key_exists('number', $issueInfo) || !array_key_exists('url', $issueInfo)) {
            throw new ErrorException('Missing Github issue info parameter');
        }

        // Apply the "Bug" label
        $issueApi->labels()->add($targetRepoAuthor, $targetRepoName, $issueInfo['number'], 'bug');

        $this->number = $issueInfo['number'];
        $this->url = $issueInfo['url'];

        return ['duplicate' => false, 'number' => $this->number, 'url' => $this->url];

    }

}
