import { NextResponse } from "next/server";

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

export async function GET() {
  try {
    const iamToken = await getIamToken();

    return NextResponse.json({
      status: "ok",
      service: "Yandex Courier Article Generator",
      version: "2.0.0",
      provider: "YandexGPT",
      yandexConnected: !!iamToken,
      topicsCount: 14
    });
  } catch (error: any) {
    return NextResponse.json({
      status: "error",
      error: error.message
    }, { status: 500 });
  }
}
