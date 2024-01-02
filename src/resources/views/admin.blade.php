@extends('layouts/app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin.css')}}">
@endsection

@section('link')
<form action="/logout" method="post">
  @csrf
  <input class="header__link" type="submit" value="logout">
</form>

@endsection

@section('content')
<div>
  <h2>Admin</h2>
  <div>
    <form action="/search" method="get">
      @csrf
      <div>
        <input type="text" name="keyword" placeholder="名前やメールアドレスを入力してください" value="{{request('keyword')}}">
        <div>
          <select name="gender" value="{{request('gender')}}">
            <option disabled selected>性別</option>
            <option value="1" @if( request('gender')==1 ) selected @endif>男性</option>
            <option value="2" @if( request('gender')==2 ) selected @endif>女性</option>
            <option value="3" @if( request('gender')==3 ) selected @endif>その他</option>
          </select>
        </div>
        <div>
          <select name="category_id">
            <option disabled selected>お問い合わせの種類</option>
            @foreach($categories as $category)
            <option value="{{ $category->id }}" @if( request('category_id')==$category->id ) selected @endif
              >{{$category->content }}
            </option>
            @endforeach
          </select>
        </div>
        <div>
          <input type="date" name="date" value="{{request('date')}}">
        </div>
      </div>
      <input type="submit" value="検索">
      <input type="submit" value="リセット" name="reset">
    </form>
  </div>

  <form action="/export" method="post">
    @csrf
    @foreach($csvData as $csv)
    <input type="hidden" name="contact_ids[]" value="{{$csv->id}}">
    @endforeach
    <input type="submit" value="エクスポート">
  </form>

  {{ $contacts->appends(request()->query())->links() }}

  <table>
    <tr>
      <th>お名前</th>
      <th>性別</th>
      <th>メールアドレス</th>
      <th>お問い合わせの種類</th>
      <th></th>
    </tr>
    @foreach($contacts as $contact)
    <tr>
      <td>{{$contact->first_name}}{{$contact->last_name}}</td>
      <td>
        @if($contact->gender == 1)
        男性
        @elseif($contact->gender == 2)
        女性
        @else
        その他
        @endif
      </td>
      <td>{{$contact->email}}</td>
      <td>{{$contact->category->content}}</td>
      <td>
        <a href="#{{$contact->id}}">詳細</a>
      </td>
    </tr>

    <div class="modal" id="{{$contact->id}}">
      <a href="#!" class="modal-overlay"></a>
      <div class="modal__inner">
        <div class="modal__content">
          <form action="/delete" method="post">
            @csrf
            <div class="modal-form__group">
              <label for="">お名前</label>
              <p>{{$contact->first_name}}{{$contact->last_name}}</p>
            </div>

            <div class="modal-form__group">
              <label for="">性別</label>
              <p>
                @if($contact->gender == 1)
                男性
                @elseif($contact->gender == 2)
                女性
                @else
                その他
                @endif
              </p>
            </div>

            <div class="modal-form__group">
              <label for="">メールアドレス</label>
              <p>{{$contact->email}}</p>
            </div>

            <div class="modal-form__group">
              <label for="">電話番号</label>
              <p>{{$contact->tell}}</p>
            </div>

            <div class="modal-form__group">
              <label for="">住所</label>
              <p>{{$contact->address}}</p>
            </div>

            <div class="modal-form__group">
              <label for="">お問い合わせの種類</label>
              <p>{{$contact->category->content}}</p>
            </div>

            <div class="modal-form__group">
              <label for="">お問い合わせ内容</label>
              <p>{{$contact->detail}}</p>
            </div>
            <input type="hidden" name="id" value="{{ $contact->id }}">
            <input type="submit" value="削除">

          </form>
        </div>

        <a href="#" class="modal__close">×</a>
      </div>
    </div>
    @endforeach
  </table>
</div>


</div>

@endsection