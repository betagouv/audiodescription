<?php

namespace App\Importer\Movie;

use App\Enum\ImportSourceType;
use App\Importer\ImportException;

/**
 * Factory class for creating movie importer instances.
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class ImporterFactory
{
    public function __construct(
        private ArteTvApiImporter $arteTvApiImporter,
        private CanalReplayApiImporter $canalReplayApiImporter,
        private CanalVodApiImporter $canalVodApiImporter,
        private FranceTvApiImporter $franceTvApiImporter,
        private LaCinetekApiImporter $laCinetekApiImporter,
        private OrangeVodCsvImporter $orangeVodCsvImporter,
        private Tf1ApiImporter $tf1ApiImporter,
    ) {
    }

  /**
   * Creates an importer based on the specified import source type.
   *
   * @param ImportSourceType $importSourceType
   *   The type of import source to create the importer for.
   *
   * @return MovieImporterInterface
   *   An instance of a class that implements the MovieImporterInterface.
   */
    public function createImporter(ImportSourceType $importSourceType): MovieImporterInterface
    {
        switch ($importSourceType) {
            case ImportSourceType::ARTE_TV_API:
                return $this->arteTvApiImporter;
            case ImportSourceType::CANAL_REPLAY_API:
                return $this->canalReplayApiImporter;
            case ImportSourceType::CANAL_VOD_API:
                return $this->canalVodApiImporter;
            case ImportSourceType::FRANCE_TV_API:
                return $this->franceTvApiImporter;
            case ImportSourceType::LACINETEK_API:
                return $this->laCinetekApiImporter;
            case ImportSourceType::ORANGE_VOD_CSV:
                return $this->orangeVodCsvImporter;
            case ImportSourceType::TF1_API:
                return $this->tf1ApiImporter;
            default:
                throw new ImportException();
        }
    }
}
