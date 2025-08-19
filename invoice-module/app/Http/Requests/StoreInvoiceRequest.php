<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'numero_facture' => 'nullable|string|max:50|unique:invoices,numero_facture',
            'date_facture' => 'required|date',
            
            // Validation des lignes de facture
            'lines' => 'required|array|min:1',
            'lines.*.description' => 'required|string|max:500',
            'lines.*.quantite' => 'required|numeric|min:0.01',
            'lines.*.prix_unitaire_ht' => 'required|numeric|min:0',
            'lines.*.taux_tva' => 'required|numeric|min:0|max:100'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'client_id.required' => 'Le client est obligatoire.',
            'client_id.exists' => 'Le client sélectionné n\'existe pas.',
            'numero_facture.unique' => 'Ce numéro de facture existe déjà.',
            'date_facture.required' => 'La date de facture est obligatoire.',
            'date_facture.date' => 'La date de facture doit être une date valide.',
            
            'lines.required' => 'Au moins une ligne de facture est requise.',
            'lines.min' => 'Au moins une ligne de facture est requise.',
            'lines.*.description.required' => 'La description de la ligne est obligatoire.',
            'lines.*.description.max' => 'La description ne peut pas dépasser 500 caractères.',
            'lines.*.quantite.required' => 'La quantité est obligatoire.',
            'lines.*.quantite.min' => 'La quantité doit être supérieure à 0.',
            'lines.*.prix_unitaire_ht.required' => 'Le prix unitaire HT est obligatoire.',
            'lines.*.prix_unitaire_ht.min' => 'Le prix unitaire HT doit être positif.',
            'lines.*.taux_tva.required' => 'Le taux de TVA est obligatoire.',
            'lines.*.taux_tva.min' => 'Le taux de TVA doit être positif.',
            'lines.*.taux_tva.max' => 'Le taux de TVA ne peut pas dépasser 100%.'
        ];
    }
}
