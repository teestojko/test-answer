<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Models\Category;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactController extends Controller
{
    // index関数を呼び出すことで、Categoryモデルから全てのカテゴリーを取得し、categories変数に格納している
    // その後、contact.blade.phpにcategories変数を渡している
    public function index()
    {
        $categories = Category::all();
        return view('contact', compact('categories'));
    }


    public function confirm
    // バリデーションのついた変数をリクエストしてる
    (ContactRequest $request)
    {
        // リクエストした全てのバリデーションのついた変数を$contactsに格納してる
        $contacts = $request->all();
        // 外部キーであるcategory_idを$requestから取り出し、それを使用して対応するカテゴリーをデータベースから検索してる
        $category = Category::find($request->category_id);
        // compact関数は、複数の変数を渡す この場合、バリデーションのついた変数と、選択されたcategoryをconfirmに渡す
        return view('confirm', compact('contacts', 'category'));
    }

    // ContactRequest $requestがなかったら、バリデーションが通った値を保存できなくなるから必要
    public function store(ContactRequest $request)
    {
        // もしリクエストにback(修正)が含まれていたら、index画面にリダイレクトする withInput()を入れると入力が消えずに残る
        if ($request->has('back')) {
            return redirect('/')->withInput();
        }

        // tel1 tel2 tel3を結合してtellに代入してる
        $request['tell'] = $request->tel_1 . $request->tel_2 . $request->tel_3;
        // Contactモデルの定義
        // ::と->は意味は一緒で、クラスの場合は::、インスタンス化された例えば＄変数は->で、どっちもアクセスするって意味
        Contact::create(
            $request->only([
                'category_id',
                'first_name',
                'last_name',
                'gender',
                'email',
                'tell',
                'address',
                'building',
                'detail'
            ])
        );

        return view('thanks');
    }

    public function admin()
    {
        // withメソッドを使用して、$contactsに、コンタクトモデルの内容と、関連づけられたcategory情報を同時に取得
        // それを１ページに７つの情報を表示する
        // ページネーションされたデータが$contacts
        $contacts = Contact::with('category')->paginate(7);
        // categoryモデルから全ての情報を取得して、$categoriesに格納してる
        $categories = Category::all();
        // Contactモデルから全ての情報を取得して、$csvDataに格納してる
        $csvData = Contact::all();

        // conpact関数は、複数の変数を渡す
        return view('admin', compact('contacts', 'categories', 'csvData'));
    }

    // (Request $request)はデータベースを介するリクエストの場合につける
    public function search(Request $request)
    {
        // リクエストの中にresetが含まれていたらリダイレクト
        if ($request->has('reset')) {
            // withInput() 打ち込んだ情報を消さない
            return redirect('/admin')->withInput();
        }
        // $queryはデータベースからデータを取得するための問い合わせ！！
        // 一度すべてのクエリを集めて、
        $query = Contact::query();

        // その中から検索条件を含んだクエリを返す
        $query = $this->getSearchQuery($request, $query);

        // ここで取り出した検索結果を表示する！！
        $contacts = $query->paginate(7);
        $csvData = $query->get();
        $categories = Category::all();
        return view('admin', compact('contacts', 'categories', 'csvData'));
    }

    public function destroy(Request $request)
    {
        // Contactの中の入力されたidを探して、削除してる
        Contact::find($request->id)->delete();
        return redirect('/admin');
    }


    public function export(Request $request)
    {
        // EloquentモデルであるContactのクエリビルダーインスタンスを作成
        // Contactモデルに対する検索やフィルタリング、並べ替えなどのクエリを実行できる
        $query = Contact::query();

        // $requestからの検索条件を使用してクエリを構築するためにgetSearchQueryを呼び出している
        // $queryを変更して検索条件を適用
        $query = $this->getSearchQuery($request, $query);

        // データベースから取得した結果を配列形式に変換してる
        // クエリビルダーを使用してデータベースからデータを取得してtoAllayメソッドで配列形式に変換
        $csvData = $query->get()->toArray();

        $csvHeader = [
            'id', 'category_id', 'first_name', 'last_name', 'gender', 'email', 'tell', 'address', 'building', 'detail', 'created_at', 'updated_at'
        ];

// StreamedResponse クラスの新しいインスタンスを作成しています。このクラスは、ストリームとしてデータを返すためのレスポンスを生成します。
// StreamedResponse のコンストラクタには、無名関数が渡されています。この無名関数は、ストリームとして出力されるデータを生成します。
// 無名関数内では、まず php://output というストリームに書き込むためのファイルポインタを取得しています。
// mb_convert_variables 関数を使用して、CSVヘッダーをUTF-8からSJIS-winに変換しています。これは、日本語などのマルチバイト文字を正しく処理するための変換です。
// CSVヘッダーをファイルに書き込んでいます。
// foreach ループを使用して、CSVデータを1行ずつ処理しています。ループ内では、各行の created_at と updated_at の日時をAsia/Tokyoのタイムゾーンに変換しています。
// ループが終了したら、ファイルを閉じています。
// StreamedResponse インスタンスには、HTTPステータスコード（この場合は200）、レスポンスヘッダー（Content-TypeとContent-Disposition）が設定されています。
// 最後に、この StreamedResponse インスタンスを返しています。これにより、生成されたCSVファイルがダウンロードされます。
        $response = new StreamedResponse(function () use ($csvHeader, $csvData) {

            $createCsvFile = fopen('php://output', 'w');

            mb_convert_variables('SJIS-win', 'UTF-8', $csvHeader);

            fputcsv($createCsvFile, $csvHeader);

            foreach ($csvData as $csv) {
                $csv['created_at'] = Date::make($csv['created_at'])->setTimezone('Asia/Tokyo')->format('Y/m/d H:i:s');
                $csv['updated_at'] = Date::make($csv['updated_at'])->setTimezone('Asia/Tokyo')->format('Y/m/d H:i:s');
                fputcsv($createCsvFile, $csv);
            }

            fclose($createCsvFile);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="contacts.csv"',
        ]);

        return $response;
    }

// このgetSearchQuery()メソッドは、与えられた検索条件に基づいてクエリを構築するためのメソッドです。主にデータベースから特定の条件に合致する結果を取得するために使用されます。

// このメソッドの機能は次のとおりです：

// メソッドは2つの引数を受け取ります：$requestと$query。
// $requestはクライアントからのHTTPリクエストを表し、検索条件を含むことが期待されます。
// $queryはEloquentクエリビルダまたはクエリビルダインスタンスであり、検索条件が適用されるデータベースクエリです。
// メソッドは、受け取った検索条件を基に、クエリを構築し、それを変更して返します。
// 最終的に、変更されたクエリが呼び出し元に返されます。
// 各条件に対して、特定の条件がリクエストされた場合にのみ、その条件がクエリに追加されます。これにより、クライアントから送信された検索条件に応じて、適切な結果が取得されます。

// 例えば、keyword、gender、category_id、dateのいずれかがリクエストされた場合、対応する条件がクエリに追加されます。これにより、柔軟な検索機能が実現されます。
    private function getSearchQuery($request, $query)
    {
        if(!empty($request->keyword)) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->keyword . '%')
                    ->orWhere('last_name', 'like', '%' . $request->keyword . '%')
                    ->orWhere('email', 'like', '%' . $request->keyword . '%');
            });
        }

        if (!empty($request->gender)) {
            $query->where('gender', '=', $request->gender);
        }

        if (!empty($request->category_id)) {
            $query->where('category_id', '=', $request->category_id);
        }

        if (!empty($request->date)) {
            $query->whereDate('created_at', '=', $request->date);
        }

        return $query;
    }
}
