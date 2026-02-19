import { NextRequest, NextResponse } from "next/server";

// YandexGPT Configuration
const YANDEX_OAUTH_TOKEN = "y0__xCfvKymARjB3RMgr-LivRY4Bzde3_n4H-tsQ3UnXgEAEYNeAg";
const YANDEX_FOLDER_ID = "b1g72166lmpl4j31mthc";

// Cache IAM token
let cachedIamToken: string | null = null;
let tokenExpiry: number = 0;

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

// Generate image with YandexART
async function generateImageWithYandexArt(prompt: string): Promise<string | null> {
  try {
    const iamToken = await getIamToken();

    console.log("[YandexART] Generating image...");

    const response = await fetch("https://llm.api.cloud.yandex.net/foundationModels/v1/imageGenerationAsync", {
      method: "POST",
      headers: {
        "Authorization": `Bearer ${iamToken}`,
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        modelUri: `art://${YANDEX_FOLDER_ID}/yandexart/latest`,
        generationOptions: {
          seed: Math.floor(Math.random() * 10000000),
          aspectRatio: {
            widthRatio: "16",
            heightRatio: "9"
          }
        },
        messages: [
          {
            weight: 1,
            text: prompt
          }
        ]
      })
    });

    if (!response.ok) {
      const errorText = await response.text();
      console.error("[YandexART] Error:", response.status, errorText);
      return null;
    }

    const data = await response.json();
    console.log("[YandexART] Response:", JSON.stringify(data).substring(0, 200));

    // Check if response has image directly
    if (data.result?.image) {
      return `data:image/png;base64,${data.result.image}`;
    }

    // Check if it's an async operation
    if (data.id) {
      // Poll for result
      const operationId = data.id;
      let attempts = 0;
      const maxAttempts = 30;

      while (attempts < maxAttempts) {
        await new Promise(resolve => setTimeout(resolve, 2000));

        const statusResponse = await fetch(
          `https://llm.api.cloud.yandex.net/foundationModels/v1/imageGeneration/${operationId}`,
          {
            headers: {
              "Authorization": `Bearer ${iamToken}`
            }
          }
        );

        if (!statusResponse.ok) {
          attempts++;
          continue;
        }

        const statusData = await statusResponse.json();

        if (statusData.result?.image) {
          return `data:image/png;base64,${statusData.result.image}`;
        }

        if (statusData.done) {
          break;
        }

        attempts++;
      }
    }

    return null;
  } catch (error) {
    console.error("[YandexART] Exception:", error);
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

// Article topics - updated for 2026
const ARTICLE_TOPICS = [
  "Как начать работать курьером Яндекс Еда в 2026 году",
  "Зарплата курьера Яндекс Еда в 2026 году: актуальные расценки",
  "Советы опытных курьеров: как увеличить заработок в 2026",
  "График работы курьера Яндекс Еда: гибкость и планирование",
  "Требования к курьерам Яндекс Еда в 2026 году",
  "Работа курьером на велосипеде: преимущества и заработок",
  "Как оформить самозанятость для работы курьером",
  "Частые ошибки начинающих курьеров и как их избежать",
  "Бонусы и акции для курьеров Яндекс Еда в 2026 году",
  "Безопасность курьера: правила и рекомендации",
  "Как работать курьером студенту: совмещение с учёбой",
  "Оборудование курьера: что нужно купить для работы",
  "Работа курьером в плохую погоду: советы и оплата",
  "Карьерный рост курьера в Яндекс Еда"
];

// Image generation prompts
const IMAGE_PROMPTS = [
  "courier on yellow bicycle delivering food in modern russian city, sunny day, professional photo, high quality",
  "delivery person with thermal bag walking in city street, russia, daytime, realistic photo",
  "young courier on electric bike with delivery backpack, urban environment, professional photography",
  "food delivery worker checking smartphone with orders, modern city background, quality photo",
  "bicycle courier in yellow jacket riding through city, russia, sunny weather, professional shot"
];

const DEFAULT_REFERRAL_LINK = "https://reg.eda.yandex.ru/?advertisement_campaign=forms_for_agents&user_invite_code=7dc31006022f4ab4bfa385dbfcc893b2&utm_content=blank";

export async function POST(request: NextRequest) {
  try {
    const body = await request.json().catch(() => ({}));

    const topic = ARTICLE_TOPICS[Math.floor(Math.random() * ARTICLE_TOPICS.length)];
    const selectedKeywords = KEYWORDS.sort(() => Math.random() - 0.5).slice(0, 4);
    const referralLink = body.referralLink || DEFAULT_REFERRAL_LINK;

    console.log(`[Article Generator] Generating: ${topic}`);

    const prompt = `Ты — профессиональный SEO-копирайтер для сайта о работе курьером в Яндекс Еда. Напиши полноценную статью на тему: "${topic}"

ВАЖНЫЕ ТРЕБОВАНИЯ:
1. Статья должна быть полезной, информативной и уникальной
2. Объём: минимум 1000 слов
3. Используй ключевые слова: ${selectedKeywords.join(", ")}
4. Структура статьи с подзаголовками H2 и H3
5. Включи списки где уместно
6. Тон: дружелюбный, мотивирующий
7. Пиши про 2026 год, сейчас 2026!

ССЫЛКА ДЛЯ РЕГИСТРАЦИИ (вставь 2-3 раза):
${referralLink}

Для каждого призыва к действию используй HTML:
<div style="text-align: center; margin: 20px 0;">
<a href="${referralLink}" style="display: inline-block; background: linear-gradient(135deg, #FFD500, #FFC300); color: #000; padding: 15px 30px; border-radius: 8px; text-decoration: none; font-weight: bold;">Стать курьером</a>
</div>

Ответь ТОЛЬКО валидным JSON (без markdown):
{
  "title": "SEO заголовок до 60 символов",
  "metaDescription": "Описание до 160 символов с ключевыми словами",
  "focusKeyword": "главное ключевое слово",
  "keywords": ["ключ1", "ключ2", "ключ3"],
  "excerpt": "Краткое описание 2-3 предложения",
  "content": "HTML контент с h2, h3, p, ul, li, strong"
}`;

    const responseText = await generateWithYandexGPT(prompt, 4000);

    let article;
    try {
      const jsonMatch = responseText.match(/\{[\s\S]*\}/);
      if (jsonMatch) {
        article = JSON.parse(jsonMatch[0]);
      } else {
        throw new Error("No JSON found");
      }
    } catch {
      article = {
        title: topic,
        metaDescription: `Статья о работе курьером в Яндекс Еда. ${selectedKeywords.slice(0, 2).join(", ")}`,
        focusKeyword: selectedKeywords[0],
        keywords: selectedKeywords,
        excerpt: `В этой статье мы расскажем о ${topic.toLowerCase()}.`,
        content: responseText
      };
    }

    // Generate image with YandexART
    const imagePrompt = IMAGE_PROMPTS[Math.floor(Math.random() * IMAGE_PROMPTS.length)];
    let imageBase64 = null;

    try {
      console.log("[Article Generator] Generating image with YandexART...");
      imageBase64 = await generateImageWithYandexArt(imagePrompt);
      console.log("[Article Generator] YandexART result:", imageBase64 ? "success" : "failed");
    } catch (imgError) {
      console.error("[Article Generator] Image generation error:", imgError);
    }

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
        imageSource: imageBase64 ? "yandexart" : "none"
      }
    };

    console.log(`[Article Generator] Success: ${result.title} (${result.metadata.wordCount} words, image: ${result.metadata.imageSource})`);

    return NextResponse.json({
      success: true,
      article: result
    });

  } catch (error: any) {
    console.error("[Article Generator] Error:", error);
    return NextResponse.json({ error: error.message }, { status: 500 });
  }
}

export async function GET() {
  try {
    const iamToken = await getIamToken();
    return NextResponse.json({
      status: "ok",
      service: "Yandex Courier Article Generator",
      version: "3.0.0",
      provider: "YandexGPT + YandexART",
      year: 2026,
      yandexConnected: !!iamToken
    });
  } catch (error: any) {
    return NextResponse.json({
      status: "error",
      error: error.message
    }, { status: 500 });
  }
}
