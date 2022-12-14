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
        <div class="d-flex justify-content-center mb-5">
            <span><a href="index.php">HOME</a> | <a href="evaluasi.php">EVALUASI</a></span>
        </div>

        <?php

        use Phpml\Classification\KNearestNeighbors;
        use Phpml\FeatureExtraction\TfIdfTransformer;
        use Phpml\FeatureExtraction\TokenCountVectorizer;
        use Phpml\Math\Distance\Dice;
        use Phpml\Math\Distance\Jaccard;
        use Phpml\Math\Distance\Cosine;
        use Phpml\Tokenization\WhitespaceTokenizer;

        require_once __DIR__ . '/vendor/autoload.php';
        include_once('conn.php');

        // metode2 nya
        $metode = ['Dice', 'Jaccard', 'Cosine'];

        $stemmerFactory = new \Sastrawi\Stemmer\StemmerFactory();
        $stemmer = $stemmerFactory->createStemmer();

        $stopwordFactory = new \Sastrawi\StopWordRemover\StopWordRemoverFactory();
        $stopword = $stopwordFactory->createStopWordRemover();

        // load random 100 - 200 data training
        $db = loadTrainingData($conn, $stopword, $stemmer, rand(100, 200));
        $data_content = $db['content']; // isi content ori
        $data_bersih = $db['bersih']; // isi content yg udah di stem + stopword
        $data_label = $db['label']; // isi label sentimen (0, 0.5, 1)

        // misahin 80% data pertama untuk data training
        // misalnya ada array isi 5 data: [1, 2, 3, 4, 5, 6, 7, 8, 9 ,10]
        // data training nanti isinya array: [1, 2, 3, 4, 5, 6, 7, 8]
        $data_content_training = array_slice($data_content, 0, (0.8 * count($data_content)));
        // 20% sisanya untuk data testing
        // isi data testing array: [9, 10]
        $data_content_testing = array_slice($data_content, (0.8 * count($data_content)), count($data_content));

        // sama aja, bedanya isinya data bersih (yg udah di stem + stopword)
        $data_bersih_training = array_slice($data_bersih, 0, (0.8 * count($data_bersih)));
        $data_bersih_testing = array_slice($data_bersih, (0.8 * count($data_bersih)), count($data_bersih));

        // sama aja, bedanya isi label sentimennya
        $label_training = array_slice($data_label, 0, (0.8 * count($data_label)));
        $label_testing = array_slice($data_label, (0.8 * count($data_label)), count($data_label));

        // ubah data bersih jadi tf idf
        $samples = preprocess($data_bersih_training, $data_bersih_testing);

        // pisahin tf idf data training sama data testing
        $tfidf_training = array_slice($samples, 0, count($data_bersih_training));
        $tfidf_testing = array_slice($samples, count($data_bersih_training), count($samples));

        // loop tiap pilihan metode
        foreach ($metode as $met) {
            $jumlah_valid = 0; // nampung berapa jumlah validnya

            // prediksi hasil sentimen dari data testing ke data training
            $predict = predict($met, $tfidf_training, $label_training, $tfidf_testing);

            echo "<h5>KNN dengan $met</h5>";
            echo '<table class="table table-responsive w-100">';
            echo '<thead><tr><th>Tweets</th><th>Sentimen Original</th><th>Sentimen Sistem</th><th>Valid</th></tr></thead>';
            echo '<tbody>';

            // tiap data testing di loop
            foreach ($data_bersih_testing as $key => $data) {
                // bikin tabel
                echo '<tr>';
                echo "<td>{$data_content_testing[$key]}</td>";

                if ($label_testing[$key] == 0.0) {
                    echo '<td>Negatif</td>';
                } else if ($label_testing[$key] == 0.5) {
                    echo '<td>Netral</td>';
                } else if ($label_testing[$key] == 1.0) {
                    echo '<td>Positif</td>';
                }

                if ($predict[$key] == 0.0) {
                    echo '<td>Negatif</td>';
                } else if ($predict[$key] == 0.5) {
                    echo '<td>Netral</td>';
                } else if ($predict[$key] == 1.0) {
                    echo '<td>Positif</td>';
                }

                // kalo label sentimen data trainng yang ke $key = hasil predict[$key] tadi
                if ($label_testing[$key] == $predict[$key]) {
                    $jumlah_valid++;
                    echo '<td>V</td>';
                } else {
                    echo '<td>X</td>';
                }

                echo "</tr>";
            }

            echo '</tbody></table>';

            // bikin rangkuman dibawah tabel
            echo '
                <div class="mt-2 mb-5 ms-5">' .
                '<p>Jumlah data testing: ' . count($data_bersih_testing) . '</p>' .
                "<p>Jumlah valid: $jumlah_valid</p>" .
                '<p>Akurasi: ' . round(($jumlah_valid / count($data_bersih_testing)) * 100) . '%</p>' .
                '</div>
            ';
        }

        // ngambil data random dari db, sebanyak limit nya
        function loadTrainingData(mysqli $conn, $stopword, $stemmer, $limit)
        {
            $data = [];
            $query = "SELECT content, isPositive FROM tweet ORDER BY rand() LIMIT $limit";

            if ($result = $conn->query($query)) {
                $i = 0;
                $content = [];
                $bersih = [];
                $label = [];

                while ($data = $result->fetch_object()) {
                    $content[$i] = $data->content; // content ori
                    $bersih[$i] = $stopword->remove($stemmer->stem($data->content)); // data bersih dari content yang udah di stem + stopword
                    $label[$i] = (float) $data->isPositive; // label sentimen
                    $i++;
                }

                $data['content'] = $content;
                $data['bersih'] = $bersih;
                $data['label'] = $label;
            }

            // hasilnya kaya:
            // [
            //  'content': ['abc abc abc', 'bca bca bca', ...],
            //  'bersih': ['abc abc abc', 'bca bca bca', ...],
            //  'label': [0, 1, 0.5, 0.5, 1, ...]
            // ]
            return $data;
        }

        function preprocess(array $training, array $uji)
        {
            // tampung sementara data training bersih kedalam $samples
            $samples = array_merge($training, $uji);

            // tf idf $samples
            $tf = new TokenCountVectorizer(new WhitespaceTokenizer());
            $tf->fit($samples);
            $tf->transform($samples);

            $tfidf = new TfIdfTransformer($samples);
            $tfidf->transform($samples);

            // kembaliin $samples yang udah jadi tf idf
            // hasilnya kaya:
            /* [
                [0, 0.1, 0.742, 0.8, ...],
                [0.98, 0.74, ...]
                ...
            ] */
            return $samples;
        }

        // tebak sentimen si data testing ke $key itu apa
        function predict(string $metode, array $data_training, array $labels, array $data_testing)
        {
            // pake perhitungan tiap metodenya
            if ($metode == 'Dice') {
                $classifier = new KNearestNeighbors(count($data_training) / 3, new Dice(0.9999));
            } else if ($metode == 'Jaccard') {
                $classifier = new KNearestNeighbors(count($data_training) / 3, new Jaccard());
            } else if ($metode == 'Cosine') {
                $classifier = new KNearestNeighbors(count($data_training) / 3, new Cosine());
            }

            $classifier->train($data_training, $labels);
            return $classifier->predict($data_testing);
        }
        ?>
    </div>
</body>