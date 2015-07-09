# Fegg Tool Trim

PHP-GDを利用して、画像のトリミングを行う[Fegg](https://github.com/genies-inc/Fegg)向けの拡張ライブラリです。

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

指定可能なパラメータと位置は下記の通りです。

|             |||
|:---|:---:|---:|
| lt |  t  | rt |
| l  |  m  |  r |
| lb |  b  | rb |
