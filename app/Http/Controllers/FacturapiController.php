<?php

namespace App\Http\Controllers;

use App\Models\CfdiUsage;
use App\Models\Client;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\FacturapiData;
use App\Models\FiscalRegime;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PaymentGroup;
use App\Models\PaymentGroupItem;
use App\Models\Specialty;
use App\Models\UserFiscalData;
use Crypt;
use Exception;
use Illuminate\Http\Request;
use Facturapi\Facturapi;
use stdClass;
use Throwable;
use Storage;

class FacturapiController extends Controller {
  public function stampPaymentGroup(Request $req) {
    try {
      $response = new \stdClass;
      $facturapi = new Facturapi(env('FACTURAPI_KEY'));

      // $payment_group_id = $req->payment_group_id;
      $payment_group_id = Crypt::decryptString($req->payment_group_id);
      $payment_group = PaymentGroup::find($payment_group_id);
      $payment_group_items = PaymentGroupItem::getItemByGroup($payment_group_id);
      $payment = Payment::getItem(null, $payment_group_items[0]->payment_id);
      $user = Client::find($payment->client_id);
      $user_id = $user->id;
      $user_fiscal_data = UserFiscalData::getItem(null, $user_id);
      if (!$user_fiscal_data->id) {
        return $this->apiRsp(422, 'La información fiscal no ha sido cargada');
      }
      $fiscal_regimes = FiscalRegime::find($user_fiscal_data->fiscal_regime_id);
      $cfdi_usege = CfdiUsage::find($user_fiscal_data->cfdi_usage_id);

      $customer = [
        "legal_name" => $user_fiscal_data->name,
        "tax_id" => $user_fiscal_data->code,
        "tax_system" => $fiscal_regimes->code,
        "address" => [
          "zip" => $user_fiscal_data->zip,
          "country" => "MEX"
        ]
      ];

      try {
        $customer = $facturapi->Customers->create($customer);
      } catch (Throwable $err) {
        return $this->apiRsp(422, 'La información fiscal no coincide con los registros del SAT');
      }

      // $price = $consultation->charge_amount / 1.16;
      $taxes = [
        [
          "type" => "IVA",
          "rate" => 0.16
        ]
      ];

      $item = [
        [
          "quantity" => 1,
          "discount" => 0,
          "product" => [
            "description" => "SERVICIOS MÉDICOS DE DOCTORES ESPECIALISTAS",
            "product_key" => "85121600",
            "unit_key" => "E48",
            "price" => $payment_group->amount,
            "tax_included" => false,
            "taxes" => $taxes
          ]
        ]
      ];

      $invoice = $facturapi->Invoices->create([
        "customer" => $customer->id,
        "items" => $item,
        "payment_form" => '04',
        "payment_method" => 'PUE',
        "use" => $cfdi_usege->code
      ]);

      $pdf = $facturapi->Invoices->download_pdf($invoice->id);
      $xml = $facturapi->Invoices->download_xml($invoice->id);
      $response->pdf = base64_encode($pdf);
      $response->xml = base64_encode($xml);

      $file_path_xml = public_path('..') . "/storage/app/private/temp/" . time() . ".xml";
      $file_path_pdf = public_path('..') . "/storage/app/private/temp/" . time() . ".pdf";
      file_put_contents($file_path_xml, $xml);
      file_put_contents($file_path_pdf, $pdf);

      EmailController::sendInvoiceFiles(null, null, $file_path_xml, $file_path_pdf);
      Storage::delete($file_path_xml);
      Storage::delete($file_path_pdf);

      // $consultation = Consultation::find($consultation_id);
      $payment_group->invoice_id = $invoice->id;
      $payment_group->save();

      return $this->apiRsp(
        200,
        'Registro retornado correctamente',
        ['item' => $response]
      );
    } catch (Throwable $err) {
      return $this->apiRsp(500, null, $err);
    }
  }
  public function stampClientPaymentGroup($req) {
    try {
      $response = new \stdClass;
      $facturapi = new Facturapi(env('FACTURAPI_KEY'));

      $payment_group_id = $req->payment_group_id;
      // $payment_group_id = Crypt::decryptString($req->payment_group_id);
      $payment_group = PaymentGroup::find($payment_group_id);
      if (!$payment_group->invoice_id) {
        $user_fiscal_data = UserFiscalData::getItem(null, $req->user()->id);
        if (!$user_fiscal_data->id) {
          return $this->apiRsp(422, 'La información fiscal no ha sido cargada');
        }
        $fiscal_regimes = FiscalRegime::find($user_fiscal_data->fiscal_regime_id);
        $cfdi_usege = CfdiUsage::find($user_fiscal_data->cfdi_usage_id);

        $customer = [
          "legal_name" => $user_fiscal_data->name,
          "tax_id" => $user_fiscal_data->code,
          "tax_system" => $fiscal_regimes->code,
          "address" => [
            "zip" => $user_fiscal_data->zip,
            "country" => "MEX"
          ]
        ];

        try {
          $customer = $facturapi->Customers->create($customer);
        } catch (Throwable $err) {
          return $this->apiRsp(422, 'La información fiscal no coincide con los registros del SAT');
        }

        $taxes = [
          [
            "type" => "IVA",
            "rate" => 0.16
          ]
        ];

        $item = [
          [
            "quantity" => 1,
            "discount" => 0,
            "product" => [
              "description" => "SERVICIOS MÉDICOS DE DOCTORES ESPECIALISTAS",
              "product_key" => "85121600",
              "unit_key" => "E48",
              "price" => $payment_group->amount,
              "tax_included" => false,
              "taxes" => $taxes
            ]
          ]
        ];

        $invoice = $facturapi->Invoices->create([
          "customer" => $customer->id,
          "items" => $item,
          "payment_form" => '04',
          "payment_method" => 'PUE',
          "use" => $cfdi_usege->code
        ]);

        $pdf = $facturapi->Invoices->download_pdf($invoice->id);
        $xml = $facturapi->Invoices->download_xml($invoice->id);
        $response->pdf = base64_encode($pdf);
        $response->xml = base64_encode($xml);

        $file_path_xml = public_path('..') . "/storage/app/private/temp/" . time() . ".xml";
        $file_path_pdf = public_path('..') . "/storage/app/private/temp/" . time() . ".pdf";
        file_put_contents($file_path_xml, $xml);
        file_put_contents($file_path_pdf, $pdf);

        EmailController::sendInvoiceFiles(null, null, $file_path_xml, $file_path_pdf);
        Storage::delete($file_path_xml);
        Storage::delete($file_path_pdf);

        $payment_group->invoice_id = $invoice->id;
        $payment_group->updated_by_id = $req->user()->id;
        $payment_group->save();

        return $this->apiRsp(
          200,
          'Factura creada correctamente'
        );
      }
      return $this->apiRsp(
        200,
        'Este pago ya ha sido facturado'
      );
    } catch (Throwable $err) {
      return $this->apiRsp(500, null, $err);
    }
  }

  public function getInvoiceFile(Request $req) {
    try {
      $facturapi = new Facturapi(env('FACTURAPI_KEY'));

      $payment_group = PaymentGroup::find($req->id);

      $file = null;
      if ($req->file_extention === 'pdf') {
        $file = $facturapi->Invoices->download_pdf($payment_group->invoice_id);
      } else {
        $file = $facturapi->Invoices->download_xml($payment_group->invoice_id);
      }

      $file = base64_encode($file);

      return $this->apiRsp(
        200,
        'Factura creada correctamente',
        ['file' => $file]
      );
    } catch (Throwable $err) {
      return $this->apiRsp(500, null, $err);
    }
  }
}
