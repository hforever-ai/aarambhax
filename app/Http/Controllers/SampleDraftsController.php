<?php

namespace App\Http\Controllers;

class SampleDraftsController extends Controller
{
    public function index()
    {
        return view('pages.sample-drafts', ['samples' => $this->samples()]);
    }

    public function show(string $slug)
    {
        $sample = collect($this->samples())->firstWhere('slug', $slug);
        abort_if(! $sample, 404);
        return view('pages.sample-draft-show', compact('sample'));
    }

    private function samples(): array
    {
        return [
            [
                'slug' => 'hc-writ-petition-civil',
                'forum' => 'CG High Court',
                'language' => 'English',
                'title' => 'Writ Petition (Civil) under Article 226',
                'subtitle' => 'Service matter — pension dispute',
                'parties' => 'Sri Mohan Lal vs State of Chhattisgarh',
                'sections' => ['Article 226', 'Article 14', 'Article 16'],
                'preview' => "**IN THE HIGH COURT OF CHHATTISGARH AT BILASPUR**\n\nW.P. (S) No. _____ of 2026\n\n**Sri Mohan Lal**, S/o Shri Ramesh Lal, aged about 62 years, retired Senior Assistant, resident of Quarter No. B-4, Ravigram, Bilaspur (C.G.) … *Petitioner*\n\n**Versus**\n\n1. State of Chhattisgarh, through the Secretary, Department of Education, Mantralaya, Naya Raipur (C.G.)\n2. Director, Public Instruction, Raipur (C.G.)\n3. District Education Officer, Bilaspur (C.G.) … *Respondents*\n\n## Synopsis\n\nThe present writ petition is filed under Article 226 of the Constitution of India seeking a direction to the respondents to release the petitioner's withheld pensionary benefits, including arrears of leave encashment and gratuity, in accordance with the Chhattisgarh Civil Services (Pension) Rules, 1976.\n\n## Grounds\n\n1. The petitioner superannuated from service on 31.05.2024 after rendering 38 years of unblemished service. Despite repeated representations, the respondents have failed to settle the petitioner's pensionary dues.\n\n2. The action of the respondents is arbitrary and violative of Articles 14 and 16 of the Constitution of India. Pension is not a bounty but a vested right earned through years of service [VERIFY: D.S. Nakara v. Union of India, (1983) 1 SCC 305].\n\n3. The petitioner has exhausted all departmental remedies. Representations dated 12.06.2024, 18.08.2024 and 24.10.2024 remain unanswered.\n\n## Prayer\n\nIt is, therefore, most respectfully prayed that this Hon'ble Court may be pleased to:\n(i) Issue a writ in the nature of mandamus directing the respondents to release the petitioner's pensionary dues forthwith with interest at 9% p.a. from the date due;\n(ii) Pass such other order as this Hon'ble Court may deem fit and proper.",
            ],
            [
                'slug' => 'district-anticipatory-bail',
                'forum' => 'CG District / Sessions',
                'language' => 'Hindi',
                'title' => 'अग्रिम जमानत आवेदन (BNSS §482)',
                'subtitle' => 'BNSS §482 के अंतर्गत अग्रिम जमानत — पूर्ण आधार',
                'parties' => 'Ram Verma vs State of CG',
                'sections' => ['BNSS §482', 'BNS §319(2)'],
                'preview' => "**माननीय न्यायाधीश महोदय,**\n**सत्र न्यायालय, बिलासपुर (छ.ग.)**\n\n**अग्रिम जमानत आवेदन — BNSS की धारा 482 के अंतर्गत**\n\nप्रकरण: M.Cr.C. _____ /2026\nFIR क्रमांक: 0145/2024, थाना — कोतवाली बिलासपुर\nधाराएं: BNS §319(2), §318(4)\n\n**आवेदक:** श्री राम वर्मा, आयु लगभग 35 वर्ष, पुत्र श्री श्याम वर्मा, निवासी — व्यापार विहार, बिलासपुर\n\n**विरुद्ध**\n\n**छत्तीसगढ़ राज्य**, थाना कोतवाली बिलासपुर के माध्यम से\n\n## आधार\n\n1. आवेदक बिलासपुर का स्थायी निवासी है तथा विगत 15 वर्षों से वहीं व्यापार कर रहा है। आवेदक के भागने की कोई संभावना नहीं है।\n\n2. आवेदक ने अनुसंधान में पूर्ण सहयोग किया है तथा भविष्य में भी आवश्यकतानुसार जांच में उपस्थित होने हेतु तैयार है।\n\n3. प्रथम सूचना रिपोर्ट केवल पुरानी व्यावसायिक रंजिश के आधार पर दर्ज कराई गई है। आरोप मनगढ़ंत हैं।\n\n4. माननीय सर्वोच्च न्यायालय द्वारा *Arnesh Kumar v. State of Bihar, (2014) 8 SCC 273* में निर्धारित सिद्धांतों के अनुसार आवेदक को अग्रिम जमानत का लाभ प्राप्त होना चाहिए।\n\n## प्रार्थना\n\nअतएव विनम्र निवेदन है कि माननीय न्यायालय आवेदक को धारा 482 BNSS के अंतर्गत अग्रिम जमानत प्रदान करने की कृपा करें।",
            ],
            [
                'slug' => 'revenue-naamantaran',
                'forum' => 'CG Revenue Court',
                'language' => 'Hindi',
                'title' => 'नामांतरण आवेदन (CG LRC §109)',
                'subtitle' => 'उत्तराधिकार के आधार पर नामांतरण',
                'parties' => 'Late Suresh Kumar (मृत्यु 2024) के विधिक उत्तराधिकारी',
                'sections' => ['CG LRC §109', 'CG LRC §110'],
                'preview' => "**तहसीलदार महोदय के समक्ष**\n**तहसील: बिलासपुर, जिला: बिलासपुर (छ.ग.)**\n\n**नामांतरण आवेदन — छत्तीसगढ़ भू-राजस्व संहिता, 1959 की धारा 109 के अंतर्गत**\n\nप्रकरण क्रमांक: ___________ /2026\n\n**आवेदक:** श्री संतोष कुमार, आयु लगभग 40 वर्ष, पुत्र स्वर्गीय श्री सुरेश कुमार, निवासी — ग्राम सिरगिट्टी, तहसील बिलासपुर, जिला बिलासपुर (छ.ग.)\n\n**भूमि का विवरण:**\n- खसरा क्रमांक: 234/2\n- खाता क्रमांक: 112\n- खतौनी क्रमांक: 45\n- रकबा: 0.45 हेक्टेयर\n- ग्राम: सिरगिट्टी\n- तहसील: बिलासपुर\n- जिला: बिलासपुर\n- भूमि उपयोग: कृषि\n\n## निवेदन\n\n1. प्रश्नगत भूमि के स्वामी स्वर्गीय श्री सुरेश कुमार थे, जिनकी मृत्यु दिनांक 15.03.2024 को हो गई।\n\n2. मृतक के विधिक उत्तराधिकारी निम्नानुसार हैं:\n   - श्री संतोष कुमार (पुत्र, आवेदक)\n   - श्रीमती सुनीता देवी (पुत्री)\n\n3. उत्तराधिकार प्रमाण पत्र संलग्न है।\n\n4. CG भू-राजस्व संहिता §109 के अंतर्गत नामांतरण की समस्त शर्तें पूर्ण की जा रही हैं।\n\n## प्रार्थना\n\nअतएव विनम्र निवेदन है कि उक्त खसरा क्रमांक 234/2 का नामांतरण आवेदक एवं श्रीमती सुनीता देवी के नाम संयुक्त रूप से कर दिया जाए।",
            ],
        ];
    }
}
