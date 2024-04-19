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
    // バリデーションをリクエストしてる
    (ContactRequest $request)
    {
        // リクエストした全てのバリデーションを$contactsに格納してる
        $contacts = $request->all();
        // 外部キーであるcategory_idを$requestから取り出し、それを使用して対応するカテゴリーをデータベースから検索してる
        $category = Category::find($request->category_id);
        // compact関数は、複数の変数を渡す
        return view('confirm', compact('contacts', 'category'));
    }

    public function store(ContactRequest $request)
    {
        // もしリクエストにback(修正)が含まれていたら、index画面にリダイレクトする　withInput()を入れると入力が消えずに残る
        if ($request->has('back')) {
            return redirect('/')->withInput();
        }

        // tel1 tel2 tel3を結合してtellに代入してる
        $request['tell'] = $request->tel_1 . $request->tel_2 . $request->tel_3;
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
        // withメソッドを使用して、$contactsに関連づけられたcategory情報を同時に取得
        // それを１ページに７つの情報を表示する
        $contacts = Contact::with('category')->paginate(7);
        // categoryモデルから全ての情報を取得して、$categoriesに格納してる
        $categories = Category::all();
        
        $csvData = Contact::all();
        return view('admin', compact('contacts', 'categories', 'csvData'));
    }

    public function search(Request $request)
    {
        if ($request->has('reset')) {
            return redirect('/admin')->withInput();
        }
        $query = Contact::query();

        $query = $this->getSearchQuery($request, $query);

        $contacts = $query->paginate(7);
        $csvData = $query->get();
        $categories = Category::all();
        return view('admin', compact('contacts', 'categories', 'csvData'));
    }

    public function destroy(Request $request)
    {
        Contact::find($request->id)->delete();
        return redirect('/admin');
    }

    public function export(Request $request)
    {
        $query = Contact::query();

        $query = $this->getSearchQuery($request, $query);

        $csvData = $query->get()->toArray();

        $csvHeader = [
            'id', 'category_id', 'first_name', 'last_name', 'gender', 'email', 'tell', 'address', 'building', 'detail', 'created_at', 'updated_at'
        ];

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
