<?php declare(strict_types=1);
/**
 * PHP version 7.4
 *
 * This source file is subject to the license that is bundled with this package in the file LICENSE.
 */

namespace PhUml\Console\Commands;

use PhUml\Parser\CodebaseDirectory;
use PhUml\Parser\CodeFinder;
use PhUml\Parser\CodeParserConfiguration;
use PhUml\Parser\SourceCodeFinder;
use PhUml\Processors\OutputFilePath;

final class StatisticsInput
{
    private CodebaseDirectory $directory;

    private OutputFilePath $outputFile;

    private CodeParserConfiguration $codeParserConfiguration;

    private bool $recursive;

    /**
     * @param string[] $arguments
     * @param string[] $options
     */
    public function __construct(array $arguments, array $options)
    {
        $this->directory = new CodebaseDirectory($arguments['directory'] ?? '');
        $this->recursive = isset($options['recursive']) && (bool) $options['recursive'];
        $this->outputFile = new OutputFilePath($arguments['output'] ?? '');
        $this->codeParserConfiguration = new CodeParserConfiguration($options);
    }

    public function outputFile(): OutputFilePath
    {
        return $this->outputFile;
    }

    public function directory(): CodebaseDirectory
    {
        return $this->directory;
    }

    public function codeFinder(): CodeFinder
    {
        return $this->recursive
            ? SourceCodeFinder::recursive()
            : SourceCodeFinder::nonRecursive();
    }

    public function codeParserConfiguration(): CodeParserConfiguration
    {
        return $this->codeParserConfiguration;
    }
}
