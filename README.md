# Analisis-Sentimen-di-Twitter-dengan-Menggunakan-Metode-KNN
Sentiment Analysis system on Twitter by comparing three similarity methods using the K-Nearest Neighbor method

Pada sistem yang saya buat ini adalah melakukan analisis sentimen pada setiap cuitan tweet yang dibuat oleh pengguna Twitter.

Proses awal pada sistem ini adalah melakukan crawling, proses crawling yang dilakukan adalah id pengguna dan isi tweet yang dibuatnya dengan sesuai keywoard yang diisi pada kolom pencarian. Proses crawling disini menggunakan metode "Sastrawi" yaitu melakukan penguraian pada isi tweet dengan menghasilkan kata dasar pada setia kalimat yang memiliki kata ber-imbuhan, hasil dari crawling dengan menggunakan metode sastrawi tersebut disebut data clean. Selanjutnya sistem akan melakukan analisis sentimen pada tweet dari hasil crawling tersebut. 

Selanjutnya dilakukan proses trainning, proses trainning ini menggunakan dataset yang saya buat terdapat pada file "Tweet.csv" isi dari file tersebut terdapat bobot nilai yaitu. Nilai 1 memiliki arti label adalah "Positif" kemudian nilai 0.5 memiliki arti label adalah "netral" dan nilai 0 memiliki arti label adalah "Negatif". Sebelum melakukan analisis sentimen sistem akan melakukan preprocessing yaitu memberikan nilai bobot awal pada TF-IDF pada data trainning. Nilai "K" yang digunakan pada metode KNN adalah jumlah data trainning / 3. 

Untuk evaluasi adalah sistem akan melakukan pembagian secara otomatis dan random, yang dimaksud pembagian otomatis dan random adalah membagi dataset menjadi, 80% data trainning dan 20% data testing. Sistem akan melakukan analisis sentimen pada 20% data testing menggunakan metode KNN dengan pendekatan similarity method yaitu, Jaccard, Dice, dan Cosine. Sistem akan membandingkan antara label hasil analisis sentimen dengan label sentimen originalnya, dan kemudian akan dihitung nilai akurasinya (%)

BITE SIZE MAKE PERFECT

