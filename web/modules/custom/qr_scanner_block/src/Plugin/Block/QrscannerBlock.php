<?php

namespace Drupal\qr_scanner_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;
use Drupal\Core\File\FileSystemInterface;

/**
 * Defines a generic custom block.
 *
 * @Block(
 *  id = "qr_scanner_block",
 *  admin_label = @Translation("Scan here on your mobile"),
 * )
 */
class QrscannerBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = \Drupal::routeMatch()->getParameter('node');
    $image_name = '';

    if (!empty($node) && $node->getType() == "products") {
      $nid = $node->id();
      $path = 'public://qr_scan';
      $image_name = 'qrcode_' . $nid . '.png';
      $uri = $path . '/' . $image_name;

      $data = $node->get('field_app_purchase_link')->getValue()[0]['uri'];
      $scan_label = $node->get('field_app_purchase_link')->getValue()[0]['title'];
      $writer = new PngWriter();

      // Create QR code.
      $qrCode = QrCode::create($data)
        ->setEncoding(new Encoding('UTF-8'))
        ->setErrorCorrectionLevel(new ErrorCorrectionLevelLow())
        ->setSize(300)
        ->setMargin(10)
        ->setRoundBlockSizeMode(new RoundBlockSizeModeMargin())
        ->setForegroundColor(new Color(0, 0, 0))
        ->setBackgroundColor(new Color(255, 255, 255));

      // Create generic logo.
      $logo = Logo::create(__DIR__ . '/assets/symfony.png')
        ->setResizeToWidth(50);

      // Create generic label.
      $label = Label::create('')
        ->setTextColor(new Color(255, 0, 0));

      $result = $writer->write($qrCode, $logo, $label);
      /** @var \Drupal\Core\File\FileSystemInterface $fileSystem */
      $fileSystem = \Drupal::service('file_system');
      $file_path = $fileSystem->realpath($uri);
      if ($fileSystem->prepareDirectory($path, FileSystemInterface::EXISTS_REPLACE)) {
        $result->saveToFile($file_path);
        $image_name = file_create_url($uri);
      }
    }

    return [
      '#theme' => 'block_qr_scanner_template',
      '#image_url' => $image_name,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}
