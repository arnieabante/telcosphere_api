<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'site',
            'id' => (string) $this->id,

            'attributes' => [
                'uuid' => $this->uuid,
                'company_logo' => $this->company_logo,
                'company_banner' => $this->company_banner,
                'company_name' => $this->company_name,
                'company_address' => $this->company_address,
                'company_email' => $this->company_email,
                'company_phone' => $this->company_phone,
                'company_telephone' => $this->company_telephone,
                'site_url' => $this->site_url,

                'invoice_id_pattern' => $this->invoice_id_pattern,
                'invoice_id_yy_last_count' => $this->invoice_id_yy_last_count,
                'receipt_id_pattern' => $this->receipt_id_pattern,
                'receipt_id_yy_last_count' => $this->receipt_id_yy_last_count,

                'account_number_pattern' => $this->account_number_pattern,
                'enable_account_number_pattern' => $this->enable_account_number_pattern,
                'account_no_last_count' => $this->account_no_last_count,

                'payment_details' => $this->payment_details,
                'isActive' => $this->is_active,

                /*
                |--------------------------------------------------------------------------
                | Homepage
                |--------------------------------------------------------------------------
                */
                'homepage' => $this->whenLoaded(
                    'homepageSettings',
                    function () {
                        return $this->homepageSettings
                            ? [
                                'hero_enabled' => $this->homepageSettings->hero_enabled,
                                'hero_title' => $this->homepageSettings->hero_title,
                                'hero_subtitle' => $this->homepageSettings->hero_subtitle,
                                'primary_button_text' => $this->homepageSettings->primary_button_text,
                                'primary_button_url' => $this->homepageSettings->primary_button_url,
                                'text_alignment' => $this->homepageSettings->text_alignment,
                                'overlay_opacity' => $this->homepageSettings->overlay_opacity,
                                'background_image' => $this->homepageSettings->background_image,
                                'hero_image' => $this->homepageSettings->hero_image,
                            ]
                            : null;
                    }
                ),

                /*
                |--------------------------------------------------------------------------
                | About Us
                |--------------------------------------------------------------------------
                */
                'about_us' => $this->whenLoaded(
                    'aboutUsSettings',
                    function () {
                        return $this->aboutUsSettings
                            ? [
                                'title' => $this->aboutUsSettings->about_us_title,
                                'information' => $this->aboutUsSettings->about_us_information,
                                'image' => $this->aboutUsSettings->about_us_image,
                            ]
                            : null;
                    }
                ),

                /*
                |--------------------------------------------------------------------------
                | Pricing Settings
                |--------------------------------------------------------------------------
                */
                'pricing' => $this->whenLoaded(
                    'pricingSettings',
                    function () {
                        return $this->pricingSettings
                            ? [
                                'title' => $this->pricingSettings->pricing_section_title,
                                'text' => $this->pricingSettings->pricing_section_text,
                            ]
                            : null;
                    }
                ),

                /*
                |--------------------------------------------------------------------------
                | Internet Plans
                |--------------------------------------------------------------------------
                */
                'internet_plans' => $this->whenLoaded(
                    'internetPlans',
                    function () {
                        return InternetplanResource::collection(
                            $this->internetPlans
                        );
                    }
                ),

                /*
                |--------------------------------------------------------------------------
                | Call To Action
                |--------------------------------------------------------------------------
                */
                'cta' => $this->whenLoaded(
                    'ctaSettings',
                    function () {
                        return $this->ctaSettings
                            ? [
                                'ctaTitle' => $this->ctaSettings->cta_title,
                                'ctaText' => $this->ctaSettings->cta_text,
                                'ctaButton' => $this->ctaSettings->cta_button,
                                'ctaLabel' => $this->ctaSettings->cta_label,
                            ]
                            : null;
                    }
                ),

                /*
                |--------------------------------------------------------------------------
                | Footer
                |--------------------------------------------------------------------------
                */
                'footer' => $this->whenLoaded(
                    'footerSettings',
                    function () {
                        return $this->footerSettings
                            ? [
                                'tagline' => $this->footerSettings->company_footer_tagline,
                                'email' => $this->footerSettings->company_email,
                                'telephone' => $this->footerSettings->company_telephone,
                                'cellphone' => $this->footerSettings->company_cellphone,
                                'address' => $this->footerSettings->company_address,
                            ]
                            : null;
                    }
                ),

                $this->mergeWhen(
                    request()->routeIs('sites.show'),
                    [
                        'siteId' => $this->site_id,
                        'createdBy' => $this->created_by,
                        'updatedBy' => $this->updated_by,
                        'createdAt' => $this->created_at,
                        'updatedAt' => $this->updated_at,
                    ]
                ),
            ],

            'links' => [],
        ];
    }
}