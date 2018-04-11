<?php
/**
 * Tool_Trimクラス
 *
 * 画像のリサイズ・トリミングを行うクラス
 *
 * @access public
 * @author Lionheart Co., Ltd.
 * @version 1.0.2
 */
class Tool_Trim
{
    private $org_data  = NULL,
            $org_image = NULL,
            $org_mode  = NULL,
            $cnv_image = NULL,
            $cnv_mode  = NULL,
            $org_path  = NULL,
            $cnv_path  = NULL,
            $exif      = array(),
            $jpeg_quality = 90;

    /**
     * 画像情報取得
     *
     * @param string $org_path
     * @param string $cnv_path
     */
    function setImage( $org_path, $cnv_path )
    {
        if(! file_exists( $org_path ) ) {
            exit( 'Not File' );
        }

        // ファイルパスを変数に格納
        $this->cnv_path = $cnv_path;
        $this->cnv_mode = strtolower( pathinfo( $cnv_path, PATHINFO_EXTENSION ) );
        $this->org_path = $org_path;

        // 変換タイプ、変換形式の整合性確認
        if(
            ! preg_match( '/jpg|gif|png/i', $this->cnv_mode )
        ) {
            exit( 'Error Extension' );
        }

        // 画像サイズを取得
        $this->org_data = array();
        $info = getimagesize( $this->org_path, $finfo );
        list( $this->org_data['width'], $this->org_data['height'] ) = $info;
        $this->org_data['type'] = $info['mime'];

        switch( $this->org_data['type'] ) {
            case "image/jpg":
            case "image/jpe":
            case "image/jpeg":
            case "image/pjpeg":
                $this->org_image = imagecreatefromjpeg( $this->org_path );
                $this->org_mode  = 'jpg';

                // Exif情報を取得しておく
                if ( function_exists( 'exif_read_data' ) ) {
                    $this->exif = @exif_read_data( $this->org_path );
                }
            break;
            case "image/gif":
                $this->org_image = imagecreatefromgif( $this->org_path );
                $this->org_mode  = 'gif';
            break;
            case "image/png":
                $this->org_image = imagecreatefrompng( $this->org_path );
                $this->org_mode  = 'png';
            break;
            default:
                die( "NotImage" );
            break;
        }

        // Exif情報に回転情報が入っている場合は回転する
        if( isset( $this->exif['Orientation'] ) ) {
            switch( $this->exif['Orientation'] ) {
                case 2:
                    // 水平反転
                    flip_horizontal( $this->org_image );
                    break;
                case 3:
                    // 180度回転
                    $this->org_image = imagerotate( $this->org_image, 180, 0 );
                    break;
                case 4:
                    // 180度回転して水平反転
                    $this->org_image = imagerotate( $this->org_image, 180, 0 );
                    flip_horizontal( $this->org_image );
                    break;
                case 5:
                    // 時計回りに90度回転して水平反転
                    $this->org_image = imagerotate( $this->org_image, 270, 0 );
                    flip_horizontal( $this->org_image );
                    $tmpWidth = $this->org_data['width'];
                    $this->org_data['width']  = $this->org_data['height'];
                    $this->org_data['height'] = $tmpWidth;
                    break;
                case 6:
                    // 時計回りに90度回転
                    $this->org_image = imagerotate( $this->org_image, 270, 0 );
                    $tmpWidth = $this->org_data['width'];
                    $this->org_data['width']  = $this->org_data['height'];
                    $this->org_data['height'] = $tmpWidth;
                    break;
                case 7:
                    // 時計回りに270度回転して水平反転
                    $this->org_image = imagerotate( $this->org_image, 90, 0 );
                    flip_horizontal( $this->org_image );
                    $tmpWidth = $this->org_data['width'];
                    $this->org_data['width']  = $this->org_data['height'];
                    $this->org_data['height'] = $tmpWidth;
                    break;
                case 8:
                    // 時計回りに270度回転
                    $this->org_image = imagerotate( $this->org_image, 90, 0 );
                    $tmpWidth = $this->org_data['width'];
                    $this->org_data['width']  = $this->org_data['height'];
                    $this->org_data['height'] = $tmpWidth;
                    break;
                case 1:
                default:
                    // そのまま
                    break;
            }
        }
    }


    /**
     * 比率を保持してリサイズ
     *
     * @param integer $w
     * @param integer $h
     */
    public function resize( $w, $h )
    {
        $w = $w > 0 ? $w : $this->org_data["width"];
        $h = $h > 0 ? $h : $this->org_data["height"];

        $sw = $w/$this->org_data["width"];
        $sh = $h/$this->org_data["height"];
        if( $sw < 1 || $sh < 1 ) {
            if( $sw <= $sh ) {
                $h = $this->org_data["height"]*$sw;
            } else {
                $w = $this->org_data["width"]*$sh;
            }
        } else {
            $w = $this->org_data["width"];
            $h = $this->org_data["height"];
        }

        $this->_makeThumbnail( $w, $h, $w, $h, $this->org_data["width"], $this->org_data["height"] );
    }

    /**
     * 比率を保持してリサイズ（足りない部分は透明 or 白に）
     *
     * @param integer $w
     * @param integer $h
     */
    public function resizeFill( $w, $h )
    {
        $w = $w > 0 ? $w : $this->org_data["width"];
        $h = $h > 0 ? $h : $this->org_data["height"];

        $sw = $w/$this->org_data["width"];
        $sh = $h/$this->org_data["height"];

        if( $sw < 1 || $sh < 1 ) {
            if( $sw <= $sh ) {
                $iw = $w;
                $ih = $this->org_data["height"]*$sw;
            } else {
                $iw = $this->org_data["width"]*$sh;
                $ih = $h;
            }
        } else {
            $iw = $this->org_data["width"];
            $ih = $this->org_data["height"];
        }

        $pos[0] = ( $w-$iw ) /2;
        $pos[1] = ( $h-$ih ) /2;

        $this->_makeThumbnail( $w, $h, $iw, $ih, $this->org_data["width"], $this->org_data["height"], $pos[0], $pos[1] );
    }

    /**
     * 比率を無視してリサイズ
     *
     * @param integer $w
     * @param integer $h
     */
    public function resizeForce( $w, $h )
    {
        $w = $w > 0 ? $w : $this->org_data["width"];
        $h = $h > 0 ? $h : $this->org_data["height"];

        $this->_makeThumbnail( $w, $h, $w, $h, $this->org_data["width"], $this->org_data["height"] );
    }

    /**
     * リサイズして指定位置でトリミング
     *
     * @param integer $w
     * @param integer $h
     */
    public function resizeTrim( $w, $h, $cp )
    {
        $w = $w > 0 ? $w : $this->org_data["width"];
        $h = $h > 0 ? $h : $this->org_data["height"];

        $sw = $w/$this->org_data["width"];
        $sh = $h/$this->org_data["height"];

        $scale = $sw <= $sh ? $sh : $sw;

        $cpW = floor( $w / $scale );
        $cpH = floor( $h / $scale );

        $rightX  = $this->org_data["width"]-$cpW;
        $centerX = $rightX - ( $this->org_data["width"]-$cpW )/2;
        $bottomY = $this->org_data["height"]-$cpH;
        $centerY = $bottomY - ( $this->org_data["height"]-$cpH )/2;

        switch( $cp ) {
            case 't':
                $pos = array( $centerX, 0 );
            break;
            case 'rt':
                $pos = array( $rightX, 0 );
            break;
            case 'l':
                $pos = array( 0, $centerY );
            break;
            case 'm':
                $pos = array( $centerX, $centerY );
            break;
            case 'r':
                $pos = array( $rightX, $centerY );
            break;
            case 'lb':
                $pos = array( 0, $centerY );
            break;
            case 'b':
                $pos = array( $centerX, $bottomY );
            break;
            case 'rb':
                $pos = array( $centerX, $bottomY );
            break;
            case 'lt':
                $pos = array( $rightX, $bottomY );
            default:
                $pos = array( 0, 0 );
            break;
        }

        $this->_makeThumbnail( $w, $h, $w, $h, $cpW, $cpH, 0, 0, $pos[0], $pos[1] );
    }

        /**
         * サムネイル作成
         *
         * @param integer $cw 生成する画像の横幅
         * @param integer $ch 生成する画像の縦幅
         * @param integer $tw 貼り付ける横幅
         * @param integer $th 貼り付ける縦幅
         * @param integer $fw 元画像から切り出す横幅
         * @param integer $fh 元画像から切り出す縦幅
         * @param integer $tx 生成する画像に貼り付けるx座標
         * @param integer $ty 生成する画像に貼り付けるy座標
         * @param integer $tx 元画像にコピーを開始するx座標
         * @param integer $ty 元画像にコピーを開始するy座標
         */
        private function _makeThumbnail( $cw, $ch, $tw, $th, $fw, $fh, $tx=0, $ty=0, $fx=0, $fy=0 )
        {
            $this->cnv_image = imagecreatetruecolor( $cw, $ch );

            // 元画像がJPG / GIFの時
            if( $this->org_mode === 'jpg' || $this->org_mode === 'gif' ) {
                // 変換先がJPGなら背景は白に
                if( $this->cnv_mode === 'jpg' ) {
                    // 背景を白に
                    $bg_color = imagecolorallocate( $this->cnv_image, 255, 255, 255 );
                    imagefill( $this->cnv_image, 0, 0, $bg_color );
                } else {
                    $alpha = imagecolortransparent( $this->org_image ); // 元画像から透過色を取得する
                    imagefill( $this->cnv_image, 0, 0, $alpha );        // その色でキャンバスを塗りつぶす
                    imagecolortransparent( $this->cnv_image, $alpha );  // 塗りつぶした色を透過色として指定する
                }
            // 元画像がPNGの時
            } else if( $this->org_mode === 'png' ) {
                // 変換先がJPG、GIFなら背景は白に
                // PNG -> GIFの変換時に他の要素まで透明にする危険性があるため
                if( $this->cnv_mode === 'jpg' || $this->cnv_mode === 'gif' ) {
                    // 背景を白に
                    $bg_color = imagecolorallocate( $this->cnv_image, 255, 255, 255 );
                    imagefill( $this->cnv_image, 0, 0, $bg_color );
                } else {
                    // 透明背景化
                    $bg_color = imagecolorallocatealpha( $this->cnv_image, 255, 255, 255, 127 );
                    imagefill( $this->cnv_image, 0, 0, $bg_color );
                    //ブレンドモードを無効にする
                    imagealphablending( $this->cnv_image, false );
                    //完全なアルファチャネル情報を保存するフラグをonにする
                    imagesavealpha( $this->cnv_image, true );
                }
            }

            imagecopyresampled(
                $this->cnv_image, //貼り付けするイメージID
                $this->org_image, //コピーする元になるイメージID
                $tx,              //int dstX (貼り付けを開始するX座標)
                $ty,              //int dstY (貼り付けを開始するY座標)
                $fx,              //int srcX (コピーを開始するX座標)
                $fy,              //int srcY (コピーを開始するY座標)
                $tw,              //int dstW (貼り付けする幅)
                $th,              //int dstH (貼り付けする高さ)
                $fw,              //int srcW (コピーする幅)
                $fh               //int srcH (コピーする高さ)
            );
        }

    /**
     * 指定パスに画像登録
     */
    public function create()
    {
        if(! file_exists( dirname( $this->cnv_path ) ) ) {
            @mkdir( dirname( $this->cnv_path ), 0777, TRUE );
        }

        switch( $this->cnv_mode ) {
            case 'jpg':
                imagejpeg( $this->cnv_image, $this->cnv_path, $this->jpeg_quality );
                break;
            case 'png':
                imagepng( $this->cnv_image, $this->cnv_path );
                break;
            case 'gif':
                imagegif( $this->cnv_image, $this->cnv_path );
                break;
        }
    }

    /**
     * 生成画像表示
     */
    public function view()
    {
        if(! file_exists( $this->cnv_path ) ) {
            return;
        }

        switch( $this->cnv_mode ) {
            case 'jpg':
                header("Content-type: image/jpeg");
                break;
            case 'png':
                header("Content-type: image/png");
                break;
            case 'gif':
                header("Content-type: image/gif");
                break;
        }

        // 表示
        echo file_get_contents( $this->cnv_path );
    }

    /**
     * デストラクタ
     */
    function __destruct()
    {
        // 削除
        if( $this->cnv_image ) {
            imagedestroy( $this->cnv_image );
        }
        if( $this->org_image ) {
            imagedestroy( $this->org_image );
        }
    }
}

/**
 * GDライブラリの反転拡張
 * via: https://gist.github.com/kijtra/990417
 */

//縦反転
function flip_vertical(&$imgsrc){
    // PHP5.5以上であればimageflipがあるので使う
    if( function_exists( 'imageflip' ) ) {
        imageflip( $imgsrc, IMG_FLIP_VERTICAL );
        return;
    }

    $x=imagesx($imgsrc);
    $y=imagesy($imgsrc);
    $flip=imagecreatetruecolor($x,$y);
    if(imagecopyresampled($flip,$imgsrc,0,0,0,$y-1,$x,$y,$x,0-$y)){
        $imgsrc = $flip;
    }
}

//横反転
function flip_horizontal(&$imgsrc){
    // PHP5.5以上であればimageflipがあるので使う
    if( function_exists( 'imageflip' ) ) {
        imageflip( $imgsrc, IMG_FLIP_HORIZONTAL );
        return;
    }

    $x=imagesx($imgsrc);
    $y=imagesy($imgsrc);
    $flip=imagecreatetruecolor($x,$y);
    if(imagecopyresampled($flip,$imgsrc,0,0,$x-1,0,$x,$y,0-$x,$y)){
        $imgsrc = $flip;
    }
}
