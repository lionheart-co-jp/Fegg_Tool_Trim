# Fegg Tool Trim

PHP-GDを利用して、画像のトリミングを行います。

## 使い方例

    $trim = $this->getClass( 'Tool/Trim' );

    // パスの指定
    $trim->setImage( 'path/to/original.jpg', 'path/to/convert.jpg' );

    // リサイズ
    $trim->resize( 640, 480 );

    // 画像の生成
    $trim->create();

    // 表示
    $trim->view();

画像を生成するだけであれば、`create`までで大丈夫です。

## トリミングの例

比率を保持してリサイズ

    $trim->resize( 640, 480 );

比率を保持してリサイズし、指定サイズに足りない部分は白 or 透明にする

    $trim->resizeFill( 640, 480 );

比率を無視してリサイズ

    $trim->resizeForce( 640, 480 );

指定位置に寄せて、指定サイズを埋める様にリサイズ

    $trim->resizeTrim( 640, 480, 'm' );

### Tool_Trim::resizeTrimのパラメータ一覧

`Tool_Trim::resizeTrim`の第三引数を変更することで、トリミングする際の位置を変更することが出来ます。

| パラメータ | 説明   |
|------------|--------|
| lt         | 左上   |
| t          | 中央上 |
| rt         | 右上   |
| l          | 左     |
| m          | 中心   |
| r          | 右     |
| lb         | 左下   |
| b          | 中央下 |
| rb         | 右下   |
