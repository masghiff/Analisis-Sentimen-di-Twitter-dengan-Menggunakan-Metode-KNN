<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisis Sentimen dengan Metode KNN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
</head>

<body>
    <div class="container p-4">
        <h1 class="text-center">Crawling Tweet dan Analisis Sentimen</h1>
        <div class="d-flex justify-content-center">
            <span><a href=".">HOME</a> | <a href="evaluasi.php">EVALUASI</a></span>
        </div>
        <form action="index.php" method="POST">
            <div class="d-flex flex-wrap align-items-center justify-content-center mt-4">
                <h4 class="me-2">Keyword</h4>
                <input type="text" name="keyword" placeholder="Masukkan kata kunci">
                <button type="submit" class="ms-2 btn btn-primary" name="search">Search</button>
            </div>
            <div class="d-flex justify-content-center align-items-center mt-4">
                <h4 class="me-2">Pilih Metode Similaritas</h4>
                <div class="d-flex flex-column">
                    <span>
                        <input type="radio" name="metode" value="dice" />
                        <label for="metode">Dice</label>
                    </span>
                    <span>
                        <input type="radio" name="metode" value="jaccard" />
                        <label for="metode">Jaccard</label>
                    </span>
                    <span>
                        <input type="radio" name="metode" value="cosine" />
                        <label for="metode">Cosine</label>
                    </span>
                </div>
            </div>
        </form>

        <?php

        use Phpml\Classification\KNearestNeighbors;
        use Phpml\FeatureExtraction\TfIdfTransformer;
        use Phpml\FeatureExtraction\TokenCountVectorizer;
        use Phpml\Math\Distance\Cosine;
        use Phpml\Math\Distance\Dice;
        use Phpml\Math\Distance\Jaccard;
        use Phpml\Tokenization\WhitespaceTokenizer;

        require_once __DIR__ . '/vendor/autoload.php';
        include_once('conn.php');
        include_once('simple_html_dom.php');

        $data_bersih_training = [];
        $label_training = [];
        $data_crawling = [];
        $data_bersih_crawling = [];

        $stemmerFactory = new \Sastrawi\Stemmer\StemmerFactory();
        $stemmer = $stemmerFactory->createStemmer();

        $stopwordFactory = new \Sastrawi\StopWordRemover\StopWordRemoverFactory();
        $stopword = $stopwordFactory->createStopWordRemover();

        if (isset($_POST['search'])) {
            $db = loadTrainingData($conn, $stopword, $stemmer);
            $data_bersih_training = $db['bersih'];
            $label_training = $db['label'];

            $keyword = $_POST['keyword'];
            $metode = $_POST['metode'];
            $query = str_replace(' ', '%20', $keyword);
            $url = "https://twitter.com/search?q=$query&f=live";

            // crawl 10 tweet
            $data_crawling = crawl($url, 10);
            

            $data_bersih_crawling = array_map(function ($data) use ($stopword, $stemmer) {
                return $stopword->remove($stemmer->stem($data['content']));
            }, $data_crawling);
            // tf idf data training dari db sama content data bersih crawling
            $samples = preprocess($data_bersih_training, $data_bersih_crawling);

            // abis data terms yg udah jadi tf idf tadi, di hapus dari samples
            $tfidf_training = array_slice($samples, 0, count($data_bersih_training));
            $tfidf_crawling = array_slice($samples, count($data_bersih_training), count($samples));

            // predict sentimen hasil crawling
            $predict = predict($metode, $tfidf_training, $label_training, $tfidf_crawling);
            $query = "INSERT INTO tweet(content, user_id, isPositive) VALUE ";
            // bikin table
            echo '<table class="table table-responsive w-100">';
            echo '<thead><tr><th>User</th><th>Tweets</th><th>Label Sentimen</th></tr></thead>';
            echo '<tbody>';
            foreach ($data_crawling as $key => $data) {
                $query .= '("' . $data['content'] . '", "' . $data['username'] . '", ' . "$predict[$key]), ";

                echo "
                <tr>
                    <td>{$data['username']}</td>
                    <td>{$data['content']}</td>";

                if ($predict[$key] == '1') {
                    echo "<td>Positif</td></tr>";
                } else if ($predict[$key] == '0.5') {
                    echo "<td>Netral</td></tr>";
                } else if ($predict[$key] == '0') {
                    echo "<td>Negatif</td></tr>";
                }
            }
            echo '</tbody>';
            echo '</table>';

            $query = rtrim($query, ', ');

            if (!$conn->query($query)) echo "Error input data training baru ke database";
        }

        // baca di evaluasi.php, cuma bedanya ini ga pake limit (ngambil semua data)
        function loadTrainingData(mysqli $conn, $stopword, $stemmer)
        {
            $data = [];
            $query = "SELECT content, isPositive FROM tweet ORDER BY rand()";

            if ($result = $conn->query($query)) {
                $i = 0;
                $bersih = [];
                $label = [];

                while ($data = $result->fetch_object()) {
                    $bersih[$i] = $stopword->remove($stemmer->stem($data->content));
                    $label[$i] = $data->isPositive;
                    $i++;
                }

                $data['bersih'] = $bersih;
                $data['label'] = $label;
            }

            return $data;
        }

        function crawl(string $url, int $count)
        {
            $data = [];
            $html = file_get_html($url);

            // crawl sebanyak $count tweet terbaru
            for ($i = 0; $i < $count; $i++) {
                $tweet = $html->find('.tweet', $i);
                $username = strip_tags($tweet->find('.fullname', 0)->innertext);
                $content = strip_tags($tweet->find('.tweet-text', 0)->innertext);
                $data[$i] = ['username' => $username, 'content' => $content];
            }

            return $data;
        }

        // baca di evaluasi.php
        function preprocess(array $data_bersih_training, array $data_bersih_crawling)
        {
            $samples = array_merge($data_bersih_training, $data_bersih_crawling);

            $tf = new TokenCountVectorizer(new WhitespaceTokenizer());
            $tf->fit($samples);
            $tf->transform($samples);

            $tfidf = new TfIdfTransformer($samples);
            $tfidf->transform($samples);

            return $samples;
        }

        // baca di evaluasi.php
        function predict(string $metode, array $data_bersih_training, array $label_training, array $data_bersih_crawling)
        {
            if ($metode == 'dice') {
                $classifier = new KNearestNeighbors(count($data_bersih_training)/3, new Dice(0.5));
            } else if ($metode == 'jaccard') {
                $classifier = new KNearestNeighbors(count($data_bersih_training)/3, new Jaccard());
            } else {
                $classifier = new KNearestNeighbors(count($data_bersih_training)/3, new Cosine());
            }

            $classifier->train($data_bersih_training, $label_training);
            return $classifier->predict($data_bersih_crawling);
        }
        ?>
    </div>
</body>

</html>