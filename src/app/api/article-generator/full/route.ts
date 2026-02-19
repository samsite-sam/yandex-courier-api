import { NextRequest, NextResponse } from "next/server";
import ZAI from "z-ai-web-dev-sdk";

// YandexGPT Configuration
const YANDEX_OAUTH_TOKEN = "y0__xCfvKymARjB3RMgr-LivRY4Bzde3_n4H-tsQ3UnXgEAEYNeAg";
const YANDEX_FOLDER_ID = "b1g72166lmpl4j31mthc";

// Cache IAM token
let cachedIamToken: string | null = null;
let tokenExpiry: number = 0;

// Cache ZAI instance
let zaiInstance: any = null;

async function getZai() {
  if (!zaiInstance) {
    zaiInstance = await ZAI.create();
  }
  return zaiInstance;
}

async function getIamToken(): Promise<string> {
  if (cachedIamToken && Date.now() < tokenExpiry - 60000) {
    return cachedIamToken;
  }

  const response = await fetch("https://iam.api.cloud.yandex.net/iam/v1/tokens", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ yandexPassportOauthToken: YANDEX_OAUTH_TOKEN })
  });

  if (!response.ok) {
    throw new Error(`Failed to get IAM token: ${response.status}`);
  }

  const data = await response.json();
  cachedIamToken = data.iamToken;
  tokenExpiry = new Date(data.expiresAt).getTime();

  return cachedIamToken;
}

// Generate text with YandexGPT
async function generateWithYandexGPT(prompt: string, maxTokens: number = 4000): Promise<string> {
  const iamToken = await getIamToken();

  const response = await fetch("https://llm.api.cloud.yandex.net/foundationModels/v1/completion", {
    method: "POST",
    headers: {
      "Authorization": `Bearer ${iamToken}`,
      "Content-Type": "application/json"
    },
    body: JSON.stringify({
      modelUri: `gpt://${YANDEX_FOLDER_ID}/yandexgpt/latest`,
      completionOptions: {
        stream: false,
        temperature: 0.8,
        maxTokens: maxTokens
      },
      messages: [
        { role: "system", text: "Ты — опытный SEO-копирайтер. Пишешь на русском языке. Отвечай валидным JSON. Сейчас 2026 год." },
        { role: "user", text: prompt }
      ]
    })
  });

  if (!response.ok) {
    throw new Error(`YandexGPT error: ${response.status}`);
  }

  const data = await response.json();
  return data.result?.alternatives?.[0]?.message?.text || "";
}

// Generate image using z-ai
async function generateImage(prompt: string): Promise<string | null> {
  try {
    console.log("[Image] Generating with z-ai...");
    const zai = await getZai();

    const response = await zai.images.generations.create({
      prompt: prompt,
      size: "1024x1024"
    });

    const base64 = response.data[0]?.base64;
    if (base64) {
      console.log("[Image] Generated successfully, size:", base64.length);
      return `data:image/png;base64,${base64}`;
    }

    return null;
  } catch (error: any) {
    console.error("[Image] Generation failed:", error.message);
    return null;
  }
}

// Keywords for SEO
const KEYWORDS = [
  "работа курьером яндекс еда",
  "вакансии курьера яндекс",
  "курьер яндекс еда зарплата",
  "как стать курьером яндекс",
  "работа в яндекс еда",
  "курьер на велосипеде",
  "курьер пешком",
  "доставка еды яндекс",
  "подработка курьером",
  "заработок курьера яндекс"
];

// Article topics - 2026
const ARTICLE_TOPICS = [
  "Как начать работать курьером Яндекс Еда в 2026 году",
  "Зарплата курьера Яндекс Еда в 2026 году: актуальные расценки",
  "Советы опытных курьеров: как увеличить заработок",
  "График работы курьера Яндекс Еда: гибкость и планирование",
  "Требования к курьерам Яндекс Еда в 2026 году",
  "Работа курьером на велосипеде: преимущества",
  "Как оформить самозанятость для работы курьером",
  "Частые ошибки начинающих курьеров",
  "Бонусы и акции для курьеров Яндекс Еда",
  "Безопасность курьера: правила",
  "Как работать курьером студенту",
  "Оборудование курьера: что нужно купить",
  "Работа курьером в плохую погоду",
  "Карьерный рост курьера в Яндекс Еда"
];

// Image prompts
const IMAGE_PROMPTS = [
  "A food delivery courier on a yellow bicycle in a modern Russian city, sunny day, professional photography, high quality",
  "Delivery worker with thermal backpack walking in urban area, daytime, realistic photo, sharp details",
  "Young courier on electric bike delivering food, city street background, professional shot",
  "Food delivery person checking smartphone with orders, modern city, quality photo",
  "Bicycle courier in yellow jacket riding through city, sunny weather, professional photography"
];

const DEFAULT_REFERRAL_LINK = "https://reg.eda.yandex.ru/?advertisement_campaign=forms_for_agents&user_invite_code=7dc31006022f4ab4bfa385dbfcc893b2&utm_content=blank";

export async function POST(request: NextRequest) {
  try {
    const body = await request.json().catch(() => ({}));

    const topic = ARTICLE_TOPICS[Math.floor(Math.random() * ARTICLE_TOPICS.length)];
    const selectedKeywords = KEYWORDS.sort(() => Math.random() - 0.5).slice(0, 4);
    const referralLink = body.referralLink || DEFAULT_REFERRAL_LINK;

    console.log(`[Article] Topic: ${topic}`);

    const prompt = `Ты — SEO-копирайтер для сайта о работе курьером в Яндекс Еда. Напиши статью: "${topic}"

Требования:
1. Объём: минимум 800 слов
2. Ключевые слова: ${selectedKeywords.join(", ")}
3. Структура: H2, H3 подзаголовки, списки
4. Тон: дружелюбный, мотивирующий
5. Сейчас 2026 год!

Реферальная ссылка (вставь 2-3 раза):
${referralLink}

CTA кнопка HTML:
<div style="text-align:center;margin:20px 0;">
<a href="${referralLink}" style="display:inline-block;background:linear-gradient(135deg,#FFD500,#FFC300);color:#000;padding:15px 30px;border-radius:8px;text-decoration:none;font-weight:bold;">Стать курьером</a>
</div>

Ответь JSON:
{"title":"заголовок","metaDescription":"описание до 160 символов","focusKeyword":"ключ","keywords":["к1","к2"],"excerpt":"кратко","content":"HTML контент"}`;

    const responseText = await generateWithYandexGPT(prompt, 4000);

    let article;
    try {
      const jsonMatch = responseText.match(/\{[\s\S]*\}/);
      if (jsonMatch) {
        article = JSON.parse(jsonMatch[0]);
      } else {
        throw new Error("No JSON");
      }
    } catch {
      article = {
        title: topic,
        metaDescription: `${selectedKeywords.slice(0, 2).join(", ")}`,
        focusKeyword: selectedKeywords[0],
        keywords: selectedKeywords,
        excerpt: topic,
        content: responseText
      };
    }

    // Generate image
    const imagePrompt = IMAGE_PROMPTS[Math.floor(Math.random() * IMAGE_PROMPTS.length)];
    const imageBase64 = await generateImage(imagePrompt);

    const result = {
      title: article.title,
      metaDescription: article.metaDescription,
      focusKeyword: article.focusKeyword,
      keywords: article.keywords || selectedKeywords,
      excerpt: article.excerpt,
      content: article.content,
      image: {
        url: imageBase64 || "",
        base64: imageBase64 || "",
        alt: article.title || topic
      },
      metadata: {
        generatedAt: new Date().toISOString(),
        topic: topic,
        wordCount: article.content?.replace(/<[^>]*>/g, "").split(/\s+/).length || 0,
        imageSource: imageBase64 ? "z-ai" : "none"
      }
    };

    console.log(`[Article] Done: ${result.title} (${result.metadata.wordCount} words, image: ${result.metadata.imageSource})`);

    return NextResponse.json({ success: true, article: result });

  } catch (error: any) {
    console.error("[Article] Error:", error);
    return NextResponse.json({ error: error.message }, { status: 500 });
  }
}

export async function GET() {
  try {
    const iamToken = await getIamToken();
    const zai = await getZai();
    return NextResponse.json({
      status: "ok",
      service: "Yandex Courier Article Generator",
      version: "3.1.0",
      provider: "YandexGPT + z-ai Images",
      year: 2026,
      yandexConnected: !!iamToken,
      zaiConnected: !!zai
    });
  } catch (error: any) {
    return NextResponse.json({ status: "error", error: error.message }, { status: 500 });
  }
}
