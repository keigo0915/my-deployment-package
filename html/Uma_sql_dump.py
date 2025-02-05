import mysql.connector
import pandas as pd
from bs4 import BeautifulSoup
from flask import Flask, request

app = Flask(__name__)

@app.route('/run-python', methods=['POST'])
def run_python():
    # フォームから送信されたデータを取得
    horse_name = request.form['inputText']
    
    # MySQL接続設定
    config = {
        'user': 'keiba',
        'password': 'baken',
        'host': '52.198.66.18',
        'database': 'raceresult'
    }
    
    # 既存のHTMLファイルを読み込む（佐々木)
    with open("/var/www/html/index.html", "r", encoding="utf-8") as file:
        soup = BeautifulSoup(file, "html.parser")
    
    # テーブルを取得（id="results-table" のテーブルを探す）（佐々木)
    table = soup.find("table", {"id": "results-table"})
    
    # SQLクエリを実行して結果を取得
    def query_horse_data(horse_name):
        connection = None  # connectionをNoneで初期化
        try:
            # MySQLサーバに接続
            connection = mysql.connector.connect(**config)
            cursor = connection.cursor()
    
            # クエリを定義（佐々木修正)
            query = '''
            SELECT X.年 * 10000 + X.月 * 100 + X.日, X.場所, X.レース名, X.確定着順, X.芝ダ, X.距離, Y.単勝配当１
            FROM race_data X
            INNER JOIN 配当a Y
            ON X.レース番号 = Y.レース番号 AND X.年 = Y.年 AND X.月 = Y.月 AND X.日 = Y.日 AND X.場所 = Y.場所
            INNER JOIN race_data Z
            ON X.レース番号 = Z.レース番号 AND X.年 = Z.年 AND X.月 = Z.月 AND X.日 = Z.日 AND X.場所 = Z.場所 AND Z.確定着順 = 1
            WHERE X.馬名 LIKE %s
            ORDER BY X.年, X.月, X.日;
            '''
            
            # パラメータ化されたクエリでhorse_nameを使用
            cursor.execute(query, ('%' + horse_name + '%',))
    
            # クエリ結果を取得して表示
            results = cursor.fetchall()
            for row in results:
                print(row)
                new_row = soup.new_tag("tr")
                for cell in row:
                    new_cell = soup.new_tag("td")
                    new_cell.string = str(cell)  # 各セルのデータを文字列として追加
                    new_row.append(new_cell)
                table.append(new_row)
    
            # 更新したHTMLをファイルに保存(佐々木)
            with open("/var/www/html/index.html", "w", encoding="utf-8") as file:
                file.write(str(soup))
    
        except mysql.connector.Error as err:
            print(f"エラーが発生しました: {err}")
        
        finally:
            # 接続が成功しているかを確認してから閉じる
            if connection and connection.is_connected():
                cursor.close()
                connection.close()
    
    # 入力された馬名に基づいてクエリを実行
    query_horse_data(horse_name)
