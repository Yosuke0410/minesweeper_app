<?

if (isset($_GET['start']) && $_GET['start'] == 1) {
	// 設定値
	$Row = (isset($_GET['row']) ? $_GET['row'] : 10);
	$Column = (isset($_GET['col']) ? $_GET['col'] : 10);
	$BomNums = (isset($_GET['bom']) ? $_GET['bom'] : 10);

	// 爆弾位置作成
	$TmpArray = range(0, ($Row * $Column) - 1);
	shuffle($TmpArray);
	$BomArray = array();
	for ($i = 0; $i < $BomNums; $i++) {
		$BomArray[] = $TmpArray[$i];
	}

	// フィールド作成
	$Field = '';
	$Location = 0;
	for ($i = 0; $i < $Row; $i++) {
		$Field .= '<tr>';
		for ($j = 0; $j < $Column; $j++) {
			$Field .= '<td class="square"';
			$Field .= ' data-location="[' . $i . ', ' . $j . ']"';
			$Field .= ' data-bomflg="' . (in_array($Location, $BomArray) ? 1 : 0) . '"'; //可視化用
			$Field .= ' data-memoflg="0"'; //メモ用
			$Field .= ' width="30px" height="30px"></td>';
			$Location++;
		}
		$Field .= '</tr>';
	}
};
?>


<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="index.css" type="text/css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
	<title>Document</title>
</head>

<body>
	<? if (isset($_GET['start']) && $_GET['start'] == 1) { ?>
		<div class="play_ground">
			<table>
				<?= $Field ?>
			</table>
		</div>
		<div class="menu" style="display: none;">
			<button onclick="window.location.href = './';">new game</button>
			<button onclick="window.location.reload();">restart</button>
		</div>
		<script>
			// 爆弾の座標を格納
			var bom_array = [];
			$('.square').each(function() {
				var value = $(this).data('bomflg');
				if (value == 1) {
					bom_array.push($(this).data('location'))
				}
			});

			//確認済み座標格納用
			var confirmed_places = [];

			//クリック処理
			$('.square').on('click contextmenu', function(e) {
				var clicked_location = $(this).data('location');
				if (e.which == 1) { //左クリック処理
					var bomflg = $(this).data('bomflg');

					if (bomflg == 1) {
						$('.menu').show();
						alert('Died');
						return false;
					}

					// 周囲の座標を取得する
					var search_places = getLocation(clicked_location);

					// 爆弾をの数をチェックする
					checkBoms(search_places);

				} else if (e.which == 3) { //右クリック処理
					if (!isNumber($(this).text()) && $(this).data('chekedflg') != '1') {
						if ($(this).attr('data-memoflg') == '0') {
							$(this).attr('data-memoflg', '1');
							$(this).text('F');
						} else {
							$(this).attr('data-memoflg', '0');
							$(this).text('');
						}
					}
				}

				checkGoal();
				return false;
			});


			// クリックした場所と周囲の座標を返す関数
			// 引数：座標
			function getLocation(clicked_location) {
				var rows = [clicked_location[0] - 1, clicked_location[0], clicked_location[0] + 1];
				var cols = [clicked_location[1] - 1, clicked_location[1], clicked_location[1] + 1];
				var result_array = [];

				rows.forEach(v1 => {
					cols.forEach(v2 => {
						result_array.push([v1, v2]);
					});
				});

				return result_array;
			}

			// 渡された座標(配列)のうち、枠外の座標を削除する関数
			// 引数：座標(配列)
			// 戻り値：座標(配列)
			function filterOutsidePlaces(search_places) {
				var result_array = [];
				// 枠外の座標を削除
				$.each(search_places, function(i, v) {
					if (!v.includes(-1) && v[0] != <?= $Row ?> && v[1] != <?= $Column ?>) {
						result_array.push(v);
					}
				})
				return result_array;
			}

			// 渡された座標(配列)のうち、確認済みの座標を削除する関数
			// 引数：座標(配列)
			// 戻り値：座標(配列)
			function filterConfirmedPlaces(search_places) {
				var result_array = [];

				// 確認済みの座標を削除
				var result_array = search_places.filter((arr1) => {
					return !confirmed_places.some((arr2) => {
						return arraysAreEqual(arr1, arr2);
					});
				});

				return result_array;
			}

			// 渡された座標(配列)の爆弾をチェックし、数を返す
			// 引数：クリックした座標、爆弾を確認する座標
			// 戻り値：なし
			function checkBoms(search_places) {
				var bomcounter = 0;
				var clicked_place = search_places[4]; //チェックする中心座標

				// 確認済み配列に格納
				confirmed_places.push(clicked_place);

				// 枠外の座標、確認済みの座標を削除する
				search_places = filterOutsidePlaces(search_places);
				search_places = filterConfirmedPlaces(search_places);

				// 座標の爆弾フラグをカウント
				for (key in search_places) {
					if ($('.square[data-location="[' + search_places[key].join(', ') + ']"]').data('bomflg') == 1) {
						bomcounter++;
					}
				}

				// 周りに爆弾がない時の処理
				if (bomcounter == 0) {
					$('[data-location="[' + clicked_place.join(', ') + ']"]').text();
					$('[data-location="[' + clicked_place.join(', ') + ']"]').css('background-color', '#9b9b9b');
					$('[data-location="[' + clicked_place.join(', ') + ']"]').attr('data-chekedflg', '1');

					// 周りに爆弾がある座標まで、チェックを繰り返す
					$.each(search_places, function(i, e) {
						var new_search_places = getLocation(e);
						checkBoms(new_search_places);
					});

				} else {
					$('[data-location="[' + clicked_place.join(', ') + ']"]').text();
					$('[data-location="[' + clicked_place.join(', ') + ']"]').text(bomcounter);
					$('[data-location="[' + clicked_place.join(', ') + ']"]').css('color', '#000000');
					return false;
				}
			}


			// 2次元配列比較用関数
			function arraysAreEqual(arr1, arr2) {
				if (arr1.length !== arr2.length) {
					return false;
				}

				for (let i = 0; i < arr1.length; i++) {
					if (arr1[i] !== arr2[i]) {
						return false;
					}
				}

				return true;
			}

			// ゴールチェック
			function checkGoal() {
				// Fの座標を取得
				var f_array = [];
				$('.square').each(function() {
					var value = $(this).text();
					if (value == 'F') {
						f_array.push($(this).data('location'))
					}
				});

				if (f_array.length == bom_array.length) {
					var result = compareArrays(f_array, bom_array);
					if (result) {
						alert('Clear!!');
					}
				}
			}

			// 配列比較用関数
			function compareArrays(array1, array2) {
				if (array1.length !== array2.length) {
					return false;
				}

				for (var i = 0; i < array1.length; i++) {
					if (array1[i] !== array2[i]) {
						return false;
					}
				}

				return true;
			}

			function isNumber(numVal) {
				// チェック条件パターン
				var pattern = /^[-]?([1-9]\d*|0)(\.\d+)?$/;
				// 数値チェック
				return pattern.test(numVal);
			}
		</script>
	<? } else { ?>
		<form action="">
			<input type="hidden" name="start" id="" value="1">
			<label for="row"><input type="text" name="row" id="" value="10">行数</label><br>
			<label for="col"><input type="text" name="col" id="" value="10">桁数</label><br>
			<label for="bom"><input type="text" name="bom" id="" value="10">地雷の数</label><br><br>
			<input type="submit" value="start">
		</form>
	<? } ?>
</body>

</html>