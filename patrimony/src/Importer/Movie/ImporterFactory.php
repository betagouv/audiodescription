<?php

namespace App\Importer\Movie;

use App\Enum\ImportSourceType;
use App\Importer\ImportException;

/**
 * Factory class for creating movie importer instances.
 */
class ImporterFactory {

  public function __construct(
    private ArteTvApiImporter    $arteTvApiImporter,
    private CanalVodApiImporter  $canalVodApiImporter,
    private CanalReplayApiImporter  $canalReplayApiImporter,
    private OrangeVodCsvImporter $orangeVodCsvImporter,
    private LaCinetekApiImporter $laCinetekApiImporter,
    private FranceTvCsvImporter  $franceTvCsvImporter,
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
  public function createImporter(ImportSourceType $importSourceType): MovieImporterInterface {
    switch ($importSourceType) {
      case ImportSourceType::ARTE_TV_API:
        return $this->arteTvApiImporter;
        break;
      case ImportSourceType::CANAL_VOD_API:
        return $this->canalVodApiImporter;
        break;
      case ImportSourceType::CANAL_REPLAY_API:
        return $this->canalReplayApiImporter;
        break;
      case ImportSourceType::ORANGE_VOD_CSV:
        return $this->orangeVodCsvImporter;
        break;
      case ImportSourceType::LACINETEK_API:
        return $this->laCinetekApiImporter;
        break;
      case ImportSourceType::FRANCE_TV_CSV:
        return $this->franceTvCsvImporter;
        break;
      default:
        throw new ImportException();
    }
  }

}
