<?php

namespace App\Http\Controllers\Api\Admin\Old;

use App\Http\Controllers\Controller;
use App\Models\CreditCard;
use App\Models\CreditCardInstallment;
use App\Models\VinovExpiry;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Nette\Schema\ValidationException;

class CreditCardController extends Controller
{
    public function getCreditCards()
    {
        try {
            $credit_cards = CreditCard::query()->where('active', 1)->get();
            return response(['message' => 'İşlem başarılı.', 'status' => 'success', 'object' => ['credit_cards' => $credit_cards]]);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'e' => $throwable->getMessage()]);
        }
    }

    public function getCreditCardById($card_id)
    {
        try {
            $credit_card = CreditCard::query()->where('id', $card_id)->first();
            $card_installment = CreditCardInstallment::query()->where('credit_card_id', $credit_card->id)->get();
            $credit_card['card_installment'] = $card_installment;
            return response(['message' => 'İşlem başarılı.', 'status' => 'success', 'object' => ['credit_card' => $credit_card]]);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'e' => $throwable->getMessage()]);
        }
    }

    public function postCreditInstallmentUpdate(Request $request, $id)
    {
        try {
            CreditCardInstallment::query()->where('id', $id)->update([
                'installment' => $request->installment,
                'installment_plus' => $request->installment_plus,
                'discount' => $request->discount,
                'partial' => $request->partial
            ]);

            return response(['message' => 'Kredi kartı taksit güncelleme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'err' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'ar' => $throwable->getMessage()]);
        }
    }

    public function getCreditCardInstallmentById($id)
    {
        try {
            $credit_card_installment = CreditCardInstallment::query()->where('id', $id)->first();
            return response(['message' => 'İşlem başarılı.', 'status' => 'success', 'object' => ['credit_card_installment' => $credit_card_installment]]);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'e' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'e' => $throwable->getMessage()]);

        }

    }

    public function addVinovExpiry(Request $request)
    {
        try {
            VinovExpiry::query()->insert([
                'expiry' => $request->expiry,
                'expiry_plus' => $request->expiry_plus,
                'discount' => $request->discount
            ]);

            return response(['message' => 'Vinov vade ekleme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'err' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'ar' => $throwable->getMessage()]);
        }
    }

    public function updateVinovExpiry(Request $request)
    {
        try {
            VinovExpiry::query()->where('id', $request->id)->update([
                'expiry' => $request->expiry,
                'expiry_plus' => $request->expiry_plus,
                'discount' => $request->discount
            ]);

            return response(['message' => 'Vinov vade güncelleme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'err' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'ar' => $throwable->getMessage()]);
        }
    }

    public function deleteVinovExpiry($id)
    {
        try {
            VinovExpiry::query()->where('id', $id)->update([
                'active' => 0
            ]);

            return response(['message' => 'Vinov vade silme işlemi başarılı.', 'status' => 'success']);
        } catch (ValidationException $validationException) {
            return response(['message' => 'Lütfen girdiğiniz bilgileri kontrol ediniz.', 'status' => 'validation-001']);
        } catch (QueryException $queryException) {
            return response(['message' => 'Hatalı sorgu.', 'status' => 'query-001', 'err' => $queryException->getMessage()]);
        } catch (\Throwable $throwable) {
            return response(['message' => 'Hatalı işlem.', 'status' => 'error-001', 'ar' => $throwable->getMessage()]);
        }
    }
}
