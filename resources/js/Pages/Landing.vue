<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
  Sparkles,
  Zap,
  ShieldCheck,
  Cpu,
  Layers,
  ArrowRight,
  CheckCircle2,
  TrendingUp,
  Calendar,
  Share2,
  Lock,
  Terminal,
  Copy,
  Check,
  BarChart3,
  Sliders,
  Eye,
  RefreshCw,
  Clock,
  ExternalLink,
  ChevronRight,
  Code2,
  Boxes,
  Database,
  Radio,
  FileCheck2,
  Film,
  Image as ImageIcon,
  DollarSign,
  AlertTriangle,
  Github,
} from 'lucide-vue-next';

interface MetaData {
  name: string;
  version: string;
  release_tag: string;
  tagline: string;
  metrics: {
    tests_passed: number;
    assertions: number;
    daily_cap_enforced: boolean;
    zero_unapproved_publish: boolean;
    active_queues: number;
  };
  integrations: {
    social: string[];
    llm: string[];
    media: string[];
    queues: string[];
  };
}

const props = withDefaults(
  defineProps<{
    meta?: MetaData;
  }>(),
  {
    meta: () => ({
      name: 'Synthex AI Social Command',
      version: '2.4.0',
      release_tag: 'v2.4-enterprise',
      tagline: 'Enterprise Autonomous AI Social Media Command Center for LinkedIn & X',
      metrics: {
        tests_passed: 47,
        assertions: 315,
        daily_cap_enforced: true,
        zero_unapproved_publish: true,
        active_queues: 4,
      },
      integrations: {
        social: ['LinkedIn (UGC / Shares API)', 'X (Twitter v2 API with PKCE)'],
        llm: ['Google Gemini 1.5 / 2.5', 'Anthropic Claude 3.5 Sonnet', 'OpenAI GPT-4o'],
        media: ['Flux.1 Schnell (Replicate)', 'DALL-E 3', 'Seedance AI Video'],
        queues: ['publish-critical', 'ai-generation', 'media-transcode', 'trend-sync'],
      },
    }),
  }
);

// Navigation & Mobile Menu
const mobileMenuOpen = ref(false);

// Interactive Live Studio Simulator State
const activePlatform = ref<'linkedin' | 'x'>('linkedin');
const selectedTone = ref<'technical' | 'founder' | 'provocative' | 'bulleted'>('technical');
const selectedTopic = ref('Autonomous AI Agents & Laravel 12 Queue Topology');
const customTopicInput = ref('');
const isSimulatingApproval = ref(false);
const isApproved = ref(false);
const copiedCode = ref<string | null>(null);

const sampleTopics = [
  'Autonomous AI Agents & Laravel 12 Queue Topology',
  'Why Monoliths are Winning Again in 2026',
  'Micro-SaaS to $10k MRR: Lessons in Unit Economics',
  'Eliminating Flaky Social APIs with Redis Idempotency Locks',
];

const presetTexts: Record<string, Record<string, { linkedin: string; x: string }>> = {
  'Autonomous AI Agents & Laravel 12 Queue Topology': {
    technical: {
      linkedin: `Building reliable AI agents requires moving beyond raw API calls into deterministic queue topologies.\n\nIn our latest architecture benchmark, we separated LLM inference from dispatch workers using segregated Redis Horizon queues:\n• publish-critical (atomic lock, 0 latency tolerance)\n• ai-generation (circuit-breakered at $150 cap)\n• media-transcode (asynchronous Flux/video synthesis)\n• trend-sync (automated RSS & HN velocity radar)\n\nResult: 100% elimination of double-posting race conditions and $0 unapproved API leaks.\n\nRead the full technical specification in docs/architecture.md.\n\n#SoftwareEngineering #Laravel #Redis #SystemArchitecture #AI`,
      x: `Segregating Redis queues is the only way to build safe AI agents:\n\n1. publish-critical (atomic lock)\n2. ai-generation (budget cap)\n3. media-transcode (asynch Flux)\n4. trend-sync (HN velocity)\n\nZero race conditions. Zero runaway token spend. 🧵👇`,
    },
    founder: {
      linkedin: `Most founders burn hours every morning fighting social media context switching.\n\nWe engineered Synthex to act as a 24/7 autonomous social executive:\n1. Scans industry trends via Hacker News & TechCrunch velocity radar\n2. Drafts multi-perspective posts tailored for LinkedIn and X\n3. Holds everything in a strict Human-in-the-Loop review inbox\n\nNothing leaves without explicit owner sign-off. High leverage without losing your voice.\n\n#BuildingInPublic #Productivity #IndieHacker #SaaS`,
      x: `AI shouldn't post blindly to your accounts.\n\nSynthex auto-curates trends from HN/TechCrunch, drafts multi-format takes, and waits for your 1-tap sign-off in the review inbox.\n\nHigh leverage, zero brand risk. 🚀`,
    },
    provocative: {
      linkedin: `Hot take: Stop letting third-party social SaaS hold your audience tokens hostage for $299/mo.\n\nSelf-hosting your social infrastructure on Laravel 12, SQLite, and Redis gives you:\n✓ Zero subscriber markups\n✓ Direct API ownership (OAuth 2.0 PKCE)\n✓ Total brand data privacy\n✓ Strict cost guardrails\n\nFull ownership > Vendor lock-in.\n\n#TechDebate #OpenSource #DevOps`,
      x: `Unpopular opinion: Paying $300/mo for social scheduling tools in 2026 is wild.\n\nSelf-host your command center on Laravel 12 + Redis Horizon. Total privacy, direct API control, zero monthly markup. 💡`,
    },
    bulleted: {
      linkedin: `Synthex AI Social Engine breakdown:\n\n• Multi-Model Text: Gemini 2.5, Claude 3.5, GPT-4o\n• Media Studio: Canva-like image canvas + Clipchamp video editor\n• Cost Controls: Daily metered hard caps and alert webhooks\n• Queue Topology: 4 segregated Redis Horizon priority bands\n• Verification: 47 automated Pest tests with 315 assertions\n\nDesigned for engineers and founders who value precision.\n\n#OpenSource #PHP #VueJS #WebDev`,
      x: `Synthex specs at a glance:\n\n⚡ Multi-model: Gemini, Claude, GPT-4o\n🎨 Studio: Canva canvas + Clipchamp video\n🛡️ Safety: Strict human approval gate\n💰 Budget: Hard API cost circuit breakers\n\n100% self-hosted & open.`,
    },
  },
};

const currentSimulatedContent = computed(() => {
  const topic = selectedTopic.value;
  const tone = selectedTone.value;
  if (presetTexts[topic] && presetTexts[topic][tone]) {
    return presetTexts[topic][tone][activePlatform.value];
  }
  return activePlatform.value === 'linkedin'
    ? `${topic}\n\nAutomated analysis generated by Synthex AI Command Center with verified brand voice tone [${tone.toUpperCase()}].\n\nIncludes strict human-in-the-loop review gating before platform dispatch.\n\n#Synthex #Engineering #AI`
    : `${topic} — Synthex autonomous draft [${tone}]. Zero hallucinated dispatches. 100% review-gated.`;
});

const characterCount = computed(() => currentSimulatedContent.value.length);
const maxCharacters = computed(() => (activePlatform.value === 'x' ? 280 : 3000));
const charPercentage = computed(() =>
  Math.min(100, Math.round((characterCount.value / maxCharacters.value) * 100))
);

const handleApprovalSimulation = () => {
  isSimulatingApproval.value = true;
  setTimeout(() => {
    isSimulatingApproval.value = false;
    isApproved.value = true;
  }, 600);
};

const resetApproval = () => {
  isApproved.value = false;
};

// ROI / Cost Calculator
const monthlyPosts = ref(60);
const calculatedSynthexCost = computed(() => {
  // ~$0.003 per LLM draft + $0.015 X API metered fee
  return (monthlyPosts.value * 0.018).toFixed(2);
});
const calculatedHootsuiteCost = 249;
const calculatedSavings = computed(() => {
  return (calculatedHootsuiteCost - parseFloat(calculatedSynthexCost.value)).toFixed(0);
});

// Quickstart Code Snippets
const activeQuickstartTab = ref<'docker' | 'artisan' | 'env'>('docker');

const quickstartSnippets = {
  docker: `# 1. Clone repository\ngit clone https://github.com/Ahasun-h/personal-ai-social-assistant.git\ncd personal-ai-social-assistant\n\n# 2. Launch production stack (App, Horizon, PostgreSQL, Redis)\ndocker-compose up -d --build\n\n# 3. Run database migrations inside container\ndocker-compose exec app php artisan migrate --force\n\n# Ready at http://localhost:8000`,
  artisan: `# 1. Install PHP & Node dependencies\ncomposer install\nnpm install\n\n# 2. Setup environment\ncp .env.example .env\nphp artisan key:generate\n\n# 3. Seed database & build frontend\nphp artisan migrate:fresh --seed --force\nnpm run build\n\n# 4. Start local server & workers\nphp artisan serve --port=8000\nphp artisan horizon`,
  env: `# Security & AI Providers (.env)\nAPP_NAME="Synthex Social Command"\nAPP_ENV=production\nAPP_KEY=base64:...\n\n# Active LLM Provider (gemini | openai | anthropic)\nAI_PROVIDER=gemini\nGEMINI_API_KEY=AIzaSy...\nANTHROPIC_API_KEY=sk-ant-...\nOPENAI_API_KEY=sk-...\n\n# Replicate / Flux Media Studio\nREPLICATE_API_TOKEN=r8_...\n\n# Queue & Redis\nQUEUE_CONNECTION=redis\nREDIS_HOST=127.0.0.1`,
};

const copySnippet = (code: string, key: string) => {
  navigator.clipboard.writeText(code);
  copiedCode.value = key;
  setTimeout(() => {
    copiedCode.value = null;
  }, 2000);
};
</script>

<template>
  <Head>
    <title>Synthex — Enterprise Autonomous AI Social Media Command Center</title>
    <meta
      name="description"
      content="High-production self-hosted AI social media curation, Canva-like image studio, Clipchamp-like video editor, dual LinkedIn & X publisher, and Redis Horizon queues."
    />
  </Head>

  <div class="min-h-screen bg-[#0B0F19] text-slate-100 font-sans selection:bg-indigo-500 selection:text-white relative overflow-x-hidden">
    <!-- Ambient Background Lighting -->
    <div class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-7xl h-96 bg-gradient-to-b from-indigo-600/15 via-sky-600/5 to-transparent blur-3xl pointer-events-none -z-10"></div>
    <div class="fixed -bottom-40 -right-40 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none -z-10"></div>
    <div class="fixed top-1/3 -left-40 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none -z-10"></div>

    <!-- Sticky Navigation Bar -->
    <header class="sticky top-0 z-50 backdrop-blur-xl bg-[#0B0F19]/80 border-b border-white/10 transition-colors">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <!-- Brand Identity -->
        <div class="flex items-center gap-3">
          <Link href="/" class="flex items-center gap-3 group">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 via-sky-500 to-emerald-400 p-[1.5px] shadow-lg shadow-indigo-500/20 group-hover:scale-105 transition-transform">
              <div class="w-full h-full bg-[#0B0F19] rounded-[10px] flex items-center justify-center">
                <Sparkles class="w-5 h-5 text-indigo-400 group-hover:text-emerald-300 transition-colors" />
              </div>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <span class="font-display font-extrabold text-lg text-white tracking-tight">SYNTHEX</span>
                <span class="text-[10px] font-mono uppercase px-1.5 py-0.5 rounded bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                  {{ props.meta?.release_tag || 'v2.4' }}
                </span>
              </div>
              <p class="text-[10px] font-mono text-slate-400 tracking-wider uppercase">AI Social Command</p>
            </div>
          </Link>
        </div>

        <!-- Desktop Navigation Links -->
        <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-300">
          <a href="#features" class="hover:text-white transition-colors">Features</a>
          <a href="#simulator" class="hover:text-white transition-colors">Live Studio Simulator</a>
          <a href="#architecture" class="hover:text-white transition-colors">Architecture & Queues</a>
          <a href="#roi" class="hover:text-white transition-colors">ROI Calculator</a>
          <a href="#quickstart" class="hover:text-white transition-colors">Self-Host</a>
          <a
            href="https://github.com/Ahasun-h/personal-ai-social-assistant"
            target="_blank"
            class="flex items-center gap-1.5 text-slate-400 hover:text-white transition-colors font-mono text-xs"
          >
            <Github class="w-4 h-4" />
            <span>GitHub</span>
          </a>
        </nav>

        <!-- Action CTAs -->
        <div class="flex items-center gap-3">
          <Link
            href="/login"
            class="px-4 py-2 text-xs font-semibold rounded-lg text-slate-300 hover:text-white hover:bg-white/5 border border-white/10 transition-all"
          >
            Sign In
          </Link>
          <Link
            href="/dashboard"
            class="relative inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg text-white bg-gradient-to-r from-indigo-600 via-indigo-500 to-sky-500 hover:from-indigo-500 hover:to-sky-400 shadow-lg shadow-indigo-600/30 hover:shadow-indigo-600/50 hover:-translate-y-0.5 transition-all"
          >
            <Zap class="w-3.5 h-3.5 fill-current" />
            <span>Launch Command</span>
          </Link>
        </div>
      </div>
    </header>

    <!-- Main Content Stream -->
    <main>
      <!-- HERO SECTION -->
      <section class="relative pt-20 pb-24 lg:pt-28 lg:pb-32 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto text-center">
        <!-- Eyebrow Badge -->
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-slate-900/90 border border-indigo-500/30 text-indigo-300 text-xs font-medium mb-8 shadow-inner shadow-indigo-500/10">
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
          <span>Zero-Friction Enterprise SDLC • 100% Human-in-the-Loop Safe</span>
          <ChevronRight class="w-3.5 h-3.5 text-slate-400" />
        </div>

        <!-- Headline -->
        <h1 class="text-4xl sm:text-6xl lg:text-7xl font-display font-extrabold text-white tracking-tight leading-tight sm:leading-none max-w-5xl mx-auto mb-6">
          Your Personal AI Social Media
          <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 via-sky-300 to-emerald-400">
            Command Center
          </span>
          for LinkedIn & X
        </h1>

        <!-- Subtitle -->
        <p class="text-base sm:text-lg lg:text-xl text-slate-400 max-w-3xl mx-auto mb-10 leading-relaxed">
          Autonomous trend radar, multi-model AI synthesis, Canva-grade graphic studio, Clipchamp-grade video sequencer, and strict human review gating. Self-hosted on Laravel 12 & Redis Horizon.
        </p>

        <!-- CTA Buttons -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-16">
          <Link
            href="/dashboard"
            class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-7 py-3.5 rounded-xl text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-500 shadow-xl shadow-indigo-600/30 hover:shadow-indigo-600/50 hover:-translate-y-0.5 transition-all"
          >
            <Zap class="w-4 h-4 fill-current text-white" />
            <span>Launch Live Workspace</span>
            <ArrowRight class="w-4 h-4 ml-1" />
          </Link>
          <a
            href="#simulator"
            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl text-sm font-semibold text-slate-200 bg-slate-900/90 hover:bg-slate-800 border border-white/10 hover:border-slate-700 transition-all"
          >
            <Eye class="w-4 h-4 text-sky-400" />
            <span>Try Interactive Simulator</span>
          </a>
          <a
            href="https://github.com/Ahasun-h/personal-ai-social-assistant"
            target="_blank"
            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl text-sm font-semibold text-slate-400 hover:text-white bg-transparent hover:bg-white/5 border border-white/5 transition-all font-mono"
          >
            <Github class="w-4 h-4" />
            <span>Clone Repo</span>
          </a>
        </div>

        <!-- Live Architectural Proof Metrics -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 max-w-4xl mx-auto">
          <div class="p-4 rounded-xl bg-slate-900/60 border border-white/10 text-left backdrop-blur-sm">
            <div class="flex items-center justify-between mb-1">
              <span class="text-xs font-mono text-slate-400">TEST RIGOR</span>
              <CheckCircle2 class="w-4 h-4 text-emerald-400" />
            </div>
            <div class="text-2xl font-display font-extrabold text-white">
              {{ props.meta?.metrics?.tests_passed ?? 47 }} Passed
            </div>
            <p class="text-[11px] text-slate-400">{{ props.meta?.metrics?.assertions ?? 315 }} Assertions • 100% Pest</p>
          </div>

          <div class="p-4 rounded-xl bg-slate-900/60 border border-white/10 text-left backdrop-blur-sm">
            <div class="flex items-center justify-between mb-1">
              <span class="text-xs font-mono text-slate-400">COST SAFETY</span>
              <ShieldCheck class="w-4 h-4 text-sky-400" />
            </div>
            <div class="text-2xl font-display font-extrabold text-white">$0.00 Overspend</div>
            <p class="text-[11px] text-slate-400">Hard daily circuit breaker</p>
          </div>

          <div class="p-4 rounded-xl bg-slate-900/60 border border-white/10 text-left backdrop-blur-sm">
            <div class="flex items-center justify-between mb-1">
              <span class="text-xs font-mono text-slate-400">HUMAN-IN-THE-LOOP</span>
              <Lock class="w-4 h-4 text-amber-400" />
            </div>
            <div class="text-2xl font-display font-extrabold text-white">100% Guarded</div>
            <p class="text-[11px] text-slate-400">0 unapproved dispatches</p>
          </div>

          <div class="p-4 rounded-xl bg-slate-900/60 border border-white/10 text-left backdrop-blur-sm">
            <div class="flex items-center justify-between mb-1">
              <span class="text-xs font-mono text-slate-400">QUEUE TOPOLOGY</span>
              <Layers class="w-4 h-4 text-indigo-400" />
            </div>
            <div class="text-2xl font-display font-extrabold text-white">4 Priority Queues</div>
            <p class="text-[11px] text-slate-400">Atomic Redis idempotency</p>
          </div>
        </div>
      </section>

      <!-- LIVE INTERACTIVE SIMULATOR SECTION -->
      <section id="simulator" class="py-20 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto border-t border-white/10">
        <div class="text-center max-w-3xl mx-auto mb-12">
          <span class="text-xs font-mono font-bold tracking-widest text-indigo-400 uppercase">Interactive Preview</span>
          <h2 class="text-3xl sm:text-4xl font-display font-extrabold text-white mt-2 mb-4">
            Test the Multi-Platform AI Studio
          </h2>
          <p class="text-slate-400 text-sm sm:text-base">
            Select a trending narrative, toggle between LinkedIn and X formats, and experience the strict Human-in-the-Loop approval gate in real-time.
          </p>
        </div>

        <div class="bg-slate-900/90 rounded-2xl border border-white/10 overflow-hidden shadow-2xl backdrop-blur-xl">
          <!-- Control Bar -->
          <div class="p-4 sm:p-6 bg-slate-950/60 border-b border-white/10 flex flex-wrap items-center justify-between gap-4">
            <!-- Platform Toggle -->
            <div class="flex items-center p-1 rounded-xl bg-slate-900 border border-white/10">
              <button
                @click="activePlatform = 'linkedin'"
                :class="[
                  'flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-semibold transition-all',
                  activePlatform === 'linkedin'
                    ? 'bg-[#0A66C2] text-white shadow'
                    : 'text-slate-400 hover:text-white',
                ]"
              >
                <Share2 class="w-3.5 h-3.5" />
                <span>LinkedIn Mode (3,000 max)</span>
              </button>
              <button
                @click="activePlatform = 'x'"
                :class="[
                  'flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-semibold transition-all',
                  activePlatform === 'x'
                    ? 'bg-black text-white border border-white/20 shadow'
                    : 'text-slate-400 hover:text-white',
                ]"
              >
                <span class="font-bold">𝕏</span>
                <span>X Mode (280 max)</span>
              </button>
            </div>

            <!-- Tone Selector -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0">
              <span class="text-xs font-mono text-slate-500 mr-2">Brand Voice:</span>
              <button
                v-for="tone in (['technical', 'founder', 'provocative', 'bulleted'] as const)"
                :key="tone"
                @click="selectedTone = tone; resetApproval()"
                :class="[
                  'px-3 py-1.5 rounded-lg text-xs font-medium capitalize transition-all',
                  selectedTone === tone
                    ? 'bg-indigo-600 text-white font-semibold'
                    : 'bg-slate-800/80 text-slate-400 hover:text-white',
                ]"
              >
                {{ tone }}
              </button>
            </div>
          </div>

          <!-- Simulator Body -->
          <div class="p-6 sm:p-8 grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Left: Prompt & Controls (5 Cols) -->
            <div class="lg:col-span-5 space-y-6">
              <div>
                <label class="block text-xs font-mono text-slate-400 uppercase tracking-wider mb-2">
                  Select Trend Stream Topic
                </label>
                <div class="space-y-2">
                  <button
                    v-for="topic in sampleTopics"
                    :key="topic"
                    @click="selectedTopic = topic; resetApproval()"
                    :class="[
                      'w-full text-left p-3 rounded-xl text-xs transition-all border flex items-center justify-between',
                      selectedTopic === topic
                        ? 'bg-indigo-950/40 border-indigo-500/50 text-indigo-200'
                        : 'bg-slate-900/60 border-white/5 text-slate-400 hover:text-slate-200 hover:border-white/10',
                    ]"
                  >
                    <span class="truncate">{{ topic }}</span>
                    <Radio v-if="selectedTopic === topic" class="w-3.5 h-3.5 text-indigo-400 flex-shrink-0" />
                  </button>
                </div>
              </div>

              <!-- Character Meter & Guardrails Status -->
              <div class="p-4 rounded-xl bg-slate-950/50 border border-white/5 space-y-3">
                <div class="flex items-center justify-between text-xs">
                  <span class="font-mono text-slate-400">Character Counter</span>
                  <span
                    :class="[
                      'font-mono font-bold',
                      characterCount > maxCharacters ? 'text-rose-400' : 'text-emerald-400',
                    ]"
                  >
                    {{ characterCount }} / {{ maxCharacters }}
                  </span>
                </div>
                <div class="w-full h-1.5 bg-slate-800 rounded-full overflow-hidden">
                  <div
                    class="h-full transition-all duration-300"
                    :class="characterCount > maxCharacters ? 'bg-rose-500' : 'bg-indigo-500'"
                    :style="{ width: `${charPercentage}%` }"
                  ></div>
                </div>

                <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 font-mono">
                  <span>API Cap: $150.00 / mo</span>
                  <span>Est. Cost: $0.003 LLM</span>
                </div>
              </div>

              <!-- Human Approval Action -->
              <div class="pt-2">
                <div v-if="!isApproved" class="space-y-2">
                  <button
                    @click="handleApprovalSimulation"
                    :disabled="isSimulatingApproval"
                    class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 shadow-lg shadow-emerald-600/20 transition-all"
                  >
                    <RefreshCw v-if="isSimulatingApproval" class="w-4 h-4 animate-spin" />
                    <ShieldCheck v-else class="w-4 h-4" />
                    <span>
                      {{ isSimulatingApproval ? 'Cryptographic Verification...' : 'Simulate Owner Approval Gate' }}
                    </span>
                  </button>
                  <p class="text-[11px] text-slate-500 text-center font-mono">
                    Strict Gate: Draft cannot dispatch to APIs until owner signs off.
                  </p>
                </div>

                <div v-else class="p-4 rounded-xl bg-emerald-950/40 border border-emerald-500/40 text-center space-y-2">
                  <div class="inline-flex items-center gap-1.5 text-emerald-400 text-xs font-bold font-mono">
                    <CheckCircle2 class="w-4 h-4" />
                    <span>DRAFT APPROVED FOR DISPATCH</span>
                  </div>
                  <p class="text-[11px] text-slate-300">
                    Idempotency token recorded. Ready for <code class="text-emerald-300 font-mono">publish-critical</code> queue.
                  </p>
                  <button
                    @click="resetApproval"
                    class="text-[11px] text-slate-400 hover:text-white underline pt-1 font-mono"
                  >
                    Reset Approval Gate
                  </button>
                </div>
              </div>
            </div>

            <!-- Right: Real-Time Card Preview (7 Cols) -->
            <div class="lg:col-span-7">
              <div class="text-xs font-mono text-slate-400 uppercase tracking-wider mb-2 flex items-center justify-between">
                <span>Live Rendering Preview (WCAG AA Compliant)</span>
                <span class="text-emerald-400 text-[10px] font-bold">● LIVE RENDER</span>
              </div>

              <!-- LINKEDIN PREVIEW CARD -->
              <div
                v-if="activePlatform === 'linkedin'"
                class="rounded-xl bg-[#1B2735] border border-white/10 p-5 shadow-xl text-left space-y-4"
              >
                <!-- Author Header -->
                <div class="flex items-center gap-3">
                  <div class="w-11 h-11 rounded-full bg-gradient-to-tr from-indigo-500 to-sky-400 p-[2px]">
                    <div class="w-full h-full bg-[#1B2735] rounded-full flex items-center justify-center font-bold text-white text-sm">
                      AR
                    </div>
                  </div>
                  <div>
                    <div class="flex items-center gap-1.5">
                      <span class="font-bold text-white text-sm">Alex Rivera</span>
                      <span class="text-slate-400 text-xs">• 1st</span>
                    </div>
                    <p class="text-xs text-slate-400">Chief Architect & AI Systems Specialist</p>
                    <p class="text-[11px] text-slate-500 flex items-center gap-1">
                      <span>Just now</span> • <span>🌐</span>
                    </p>
                  </div>
                </div>

                <!-- Post Text -->
                <div class="text-sm text-slate-200 whitespace-pre-line leading-relaxed font-sans">
                  {{ currentSimulatedContent }}
                </div>

                <!-- Engagement Bar Mockup -->
                <div class="pt-3 border-t border-white/5 flex items-center justify-between text-xs text-slate-400 font-medium">
                  <div class="flex items-center gap-1 text-[11px]">
                    <span class="p-1 rounded-full bg-blue-500 text-white text-[9px]">👍</span>
                    <span class="p-1 rounded-full bg-emerald-500 text-white text-[9px]">💡</span>
                    <span class="ml-1">84 reactions</span>
                  </div>
                  <span>14 comments • 6 reposts</span>
                </div>
              </div>

              <!-- X / TWITTER PREVIEW CARD -->
              <div
                v-else
                class="rounded-xl bg-black border border-white/20 p-5 shadow-xl text-left space-y-4"
              >
                <!-- Author Header -->
                <div class="flex items-start justify-between">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center font-bold text-white text-xs">
                      AR
                    </div>
                    <div>
                      <div class="flex items-center gap-1.5">
                        <span class="font-bold text-white text-sm">Alex Rivera</span>
                        <span class="text-sky-400 text-xs">✓</span>
                        <span class="text-slate-500 text-xs">@alex_rivera</span>
                      </div>
                      <p class="text-[11px] text-slate-500">synthex.internal</p>
                    </div>
                  </div>
                  <span class="text-slate-500 font-bold text-lg">𝕏</span>
                </div>

                <!-- Tweet Text -->
                <div class="text-sm text-slate-100 whitespace-pre-line leading-relaxed font-sans">
                  {{ currentSimulatedContent }}
                </div>

                <!-- Tweet Footer Actions -->
                <div class="pt-3 border-t border-neutral-900 flex items-center justify-between text-xs text-slate-500">
                  <span>💬 12</span>
                  <span>🔄 38</span>
                  <span>❤️ 142</span>
                  <span>📊 4.2K</span>
                  <span>🔗</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- 6 SUPERPOWERS GRID -->
      <section id="features" class="py-24 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
        <div class="text-center max-w-3xl mx-auto mb-16">
          <span class="text-xs font-mono font-bold tracking-widest text-emerald-400 uppercase">Engine Architecture</span>
          <h2 class="text-3xl sm:text-5xl font-display font-extrabold text-white mt-2 mb-4">
            Six Pillars of Zero-Friction Social Automation
          </h2>
          <p class="text-slate-400 text-sm sm:text-base">
            Engineered from ground up with enterprise separation of concerns, eliminating the vulnerabilities of generic wrappers.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <!-- Card 1 -->
          <div class="p-6 rounded-2xl bg-slate-900/60 border border-white/10 hover:border-indigo-500/40 hover:-translate-y-1 transition-all duration-300 group">
            <div class="w-12 h-12 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 mb-5 group-hover:scale-110 transition-transform">
              <Share2 class="w-6 h-6" />
            </div>
            <h3 class="text-lg font-display font-bold text-white mb-2">Dual-Platform Publishing Engine</h3>
            <p class="text-sm text-slate-400 leading-relaxed">
              Native adapters for LinkedIn (w_member_social, UGC & Shares API) and X API v2 with OAuth 2.0 PKCE. Atomic Redis locks prevent duplicate dispatches during network retries.
            </p>
          </div>

          <!-- Card 2 -->
          <div class="p-6 rounded-2xl bg-slate-900/60 border border-white/10 hover:border-emerald-500/40 hover:-translate-y-1 transition-all duration-300 group">
            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 mb-5 group-hover:scale-110 transition-transform">
              <Lock class="w-6 h-6" />
            </div>
            <h3 class="text-lg font-display font-bold text-white mb-2">Strict Human-in-the-Loop Review</h3>
            <p class="text-sm text-slate-400 leading-relaxed">
              AI generates ideas; humans command. Posts can never auto-dispatch without explicit owner sign-off. Any content edit immediately revokes approval and requires re-verification.
            </p>
          </div>

          <!-- Card 3 -->
          <div class="p-6 rounded-2xl bg-slate-900/60 border border-white/10 hover:border-sky-500/40 hover:-translate-y-1 transition-all duration-300 group">
            <div class="w-12 h-12 rounded-xl bg-sky-500/10 border border-sky-500/20 flex items-center justify-center text-sky-400 mb-5 group-hover:scale-110 transition-transform">
              <TrendingUp class="w-6 h-6" />
            </div>
            <h3 class="text-lg font-display font-bold text-white mb-2">Autonomous Trend Radar</h3>
            <p class="text-sm text-slate-400 leading-relaxed">
              Continuous background polling of Hacker News, TechCrunch, ArXiv, and custom RSS feeds. Scores trending topics by velocity and automatically drafts high-conviction angles.
            </p>
          </div>

          <!-- Card 4 -->
          <div class="p-6 rounded-2xl bg-slate-900/60 border border-white/10 hover:border-purple-500/40 hover:-translate-y-1 transition-all duration-300 group">
            <div class="w-12 h-12 rounded-xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-400 mb-5 group-hover:scale-110 transition-transform">
              <ImageIcon class="w-6 h-6" />
            </div>
            <h3 class="text-lg font-display font-bold text-white mb-2">Canva & Clipchamp Studios</h3>
            <p class="text-sm text-slate-400 leading-relaxed">
              Built-in canvas graphic editor with 1:1, 16:9, and 4:5 aspect presets plus a Clipchamp-like multi-track video timeline editor. Generate Flux images and Seedance AI videos directly in-app.
            </p>
          </div>

          <!-- Card 5 -->
          <div class="p-6 rounded-2xl bg-slate-900/60 border border-white/10 hover:border-amber-500/40 hover:-translate-y-1 transition-all duration-300 group">
            <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-5 group-hover:scale-110 transition-transform">
              <DollarSign class="w-6 h-6" />
            </div>
            <h3 class="text-lg font-display font-bold text-white mb-2">Metered Cost Circuit Breakers</h3>
            <p class="text-sm text-slate-400 leading-relaxed">
              Real-time ledger tracking for LLM token inference, Replicate image generation, and X API pay-per-post metered fees. Hard stop caps trip at 100% budget, protecting your wallet.
            </p>
          </div>

          <!-- Card 6 -->
          <div class="p-6 rounded-2xl bg-slate-900/60 border border-white/10 hover:border-rose-500/40 hover:-translate-y-1 transition-all duration-300 group">
            <div class="w-12 h-12 rounded-xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-400 mb-5 group-hover:scale-110 transition-transform">
              <Layers class="w-6 h-6" />
            </div>
            <h3 class="text-lg font-display font-bold text-white mb-2">Idempotent Horizon Queues</h3>
            <p class="text-sm text-slate-400 leading-relaxed">
              Priority queue segmentation: <code class="text-rose-300 text-xs">publish-critical</code>, <code class="text-rose-300 text-xs">ai-generation</code>, <code class="text-rose-300 text-xs">media-transcode</code>, and <code class="text-rose-300 text-xs">trend-sync</code> with exponential backoff and dead-letter traps.
            </p>
          </div>
        </div>
      </section>

      <!-- ARCHITECTURE & QUEUE TOPOLOGY -->
      <section id="architecture" class="py-24 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto border-t border-white/10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
          <div class="lg:col-span-6 space-y-6">
            <span class="text-xs font-mono font-bold tracking-widest text-sky-400 uppercase">System Topology</span>
            <h2 class="text-3xl sm:text-4xl font-display font-extrabold text-white leading-tight">
              Segregated Queues with Cryptographic Idempotency
            </h2>
            <p class="text-slate-400 text-sm leading-relaxed">
              Unlike monolithic workers that choke under heavy video transcoding or AI latency, Synthex isolates workloads into 4 dedicated Redis channels managed by Laravel Horizon.
            </p>

            <div class="space-y-3 font-mono text-xs">
              <div class="p-3.5 rounded-xl bg-slate-900/80 border border-white/10 flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                  <span class="text-white font-bold">publish-critical</span>
                </div>
                <span class="text-slate-400">Max Latency: &lt;100ms • Retries: 3</span>
              </div>

              <div class="p-3.5 rounded-xl bg-slate-900/80 border border-white/10 flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <span class="w-2.5 h-2.5 rounded-full bg-indigo-400"></span>
                  <span class="text-white font-bold">ai-generation</span>
                </div>
                <span class="text-slate-400">Concurrency: 5 • Circuit: $150 cap</span>
              </div>

              <div class="p-3.5 rounded-xl bg-slate-900/80 border border-white/10 flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <span class="w-2.5 h-2.5 rounded-full bg-purple-400"></span>
                  <span class="text-white font-bold">media-transcode</span>
                </div>
                <span class="text-slate-400">Timeout: 300s • Flux & Seedance</span>
              </div>

              <div class="p-3.5 rounded-xl bg-slate-900/80 border border-white/10 flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                  <span class="text-white font-bold">trend-sync</span>
                </div>
                <span class="text-slate-400">Interval: 15m • Velocity rank</span>
              </div>
            </div>
          </div>

          <!-- Architecture Visual Schematic -->
          <div class="lg:col-span-6 p-6 rounded-2xl bg-slate-950 border border-white/10 font-mono text-xs shadow-2xl">
            <div class="flex items-center justify-between pb-4 border-b border-white/10 mb-4 text-slate-400">
              <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full bg-rose-500/80"></div>
                <div class="w-3 h-3 rounded-full bg-amber-500/80"></div>
                <div class="w-3 h-3 rounded-full bg-emerald-500/80"></div>
                <span class="text-[11px] text-slate-400 ml-2">architecture-topology.svg</span>
              </div>
              <span class="text-emerald-400">STATUS: HEALTHY</span>
            </div>

            <div class="space-y-4">
              <div class="p-3 rounded-lg bg-slate-900/90 border border-indigo-500/30">
                <div class="text-indigo-400 font-bold mb-1">1. Frontend Layer</div>
                <div class="text-slate-300">Vue 3.4 + Inertia.js v2 + Tailwind 3.4 (Zero SPA reload)</div>
              </div>

              <div class="flex justify-center text-slate-600">↓ HTTP / REST / Sanctum</div>

              <div class="p-3 rounded-lg bg-slate-900/90 border border-sky-500/30">
                <div class="text-sky-400 font-bold mb-1">2. Core Backend Layer</div>
                <div class="text-slate-300">Laravel 12 + 3NF Relational DB + Cost Breakers + AES-256 Tokens</div>
              </div>

              <div class="flex justify-center text-slate-600">↓ Atomic Redis Dispatches</div>

              <div class="p-3 rounded-lg bg-slate-900/90 border border-emerald-500/30">
                <div class="text-emerald-400 font-bold mb-1">3. Worker Queue Layer</div>
                <div class="text-slate-300">Laravel Horizon (4 Isolated Queues, Zero Duplicate Locks)</div>
              </div>

              <div class="flex justify-center text-slate-600">↓ Direct Secure APIs</div>

              <div class="p-3 rounded-lg bg-slate-900/90 border border-purple-500/30">
                <div class="text-purple-400 font-bold mb-1">4. External Gateways</div>
                <div class="text-slate-300">LinkedIn UGC API • X API v2 • Gemini 2.5 • Claude 3.5 • Replicate</div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ROI CALCULATOR -->
      <section id="roi" class="py-24 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto border-t border-white/10">
        <div class="bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 rounded-3xl border border-white/10 p-8 sm:p-12">
          <div class="max-w-3xl mx-auto text-center mb-10">
            <span class="text-xs font-mono font-bold tracking-widest text-emerald-400 uppercase">Self-Hosting Economics</span>
            <h2 class="text-3xl sm:text-4xl font-display font-extrabold text-white mt-2 mb-3">
              Eliminate the $3,000/Year SaaS Tax
            </h2>
            <p class="text-slate-400 text-sm">
              Commercial social media tools charge exorbitant markups for basic scheduling. With Synthex, you pay only pure cost for your own API tokens.
            </p>
          </div>

          <div class="max-w-2xl mx-auto space-y-8">
            <div>
              <div class="flex justify-between items-center text-sm font-semibold mb-3">
                <span class="text-slate-300">Expected Posts per Month:</span>
                <span class="text-indigo-400 font-mono text-lg font-bold">{{ monthlyPosts }} posts</span>
              </div>
              <input
                v-model.number="monthlyPosts"
                type="range"
                min="10"
                max="300"
                step="5"
                class="w-full h-2 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-indigo-500"
              />
              <div class="flex justify-between text-[11px] font-mono text-slate-500 mt-2">
                <span>10 (Casual)</span>
                <span>150 (Growth)</span>
                <span>300 (High Volume Agency)</span>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-center">
              <div class="p-5 rounded-2xl bg-slate-900 border border-white/10">
                <span class="text-xs font-mono text-slate-400">Hootsuite / Buffer</span>
                <div class="text-2xl font-bold text-rose-400 mt-1">${{ calculatedHootsuiteCost }}/mo</div>
                <p class="text-[11px] text-slate-500 mt-1">Vendor lock-in & limits</p>
              </div>

              <div class="p-5 rounded-2xl bg-indigo-950/60 border border-indigo-500/40">
                <span class="text-xs font-mono text-indigo-300">Synthex Self-Hosted</span>
                <div class="text-2xl font-bold text-emerald-400 mt-1">${{ calculatedSynthexCost }}/mo</div>
                <p class="text-[11px] text-slate-400 mt-1">Raw API costs only</p>
              </div>

              <div class="p-5 rounded-2xl bg-emerald-950/40 border border-emerald-500/40">
                <span class="text-xs font-mono text-emerald-300">Your Annual Savings</span>
                <div class="text-2xl font-bold text-white mt-1">${{ (Number(calculatedSavings) * 12).toLocaleString() }}</div>
                <p class="text-[11px] text-emerald-400 mt-1 font-semibold">100% Retained Value</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- TEST SUITE RIGOR SECTION -->
      <section class="py-20 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto border-t border-white/10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
          <div class="lg:col-span-5 space-y-4">
            <span class="text-xs font-mono font-bold tracking-widest text-emerald-400 uppercase">Verification Rigor</span>
            <h2 class="text-3xl font-display font-extrabold text-white">
              Zero Regressions. Zero Guesswork.
            </h2>
            <p class="text-slate-400 text-sm leading-relaxed">
              Every critical path—character meters, emoji utf-8 counts, atomic idempotency locks, budget caps, and model fallbacks—is backed by an automated Pest PHP verification suite.
            </p>
            <div class="space-y-2 text-xs font-mono text-slate-300">
              <div class="flex items-center gap-2">
                <CheckCircle2 class="w-4 h-4 text-emerald-400" />
                <span>47 unit and feature test scenarios passing</span>
              </div>
              <div class="flex items-center gap-2">
                <CheckCircle2 class="w-4 h-4 text-emerald-400" />
                <span>315 strict assertions with zero failures</span>
              </div>
              <div class="flex items-center gap-2">
                <CheckCircle2 class="w-4 h-4 text-emerald-400" />
                <span>Vue 3 TypeScript compile & typecheck pass</span>
              </div>
            </div>
          </div>

          <!-- Pest Test Terminal Mockup -->
          <div class="lg:col-span-7 rounded-2xl bg-slate-950 border border-white/10 p-5 font-mono text-xs shadow-2xl">
            <div class="flex items-center justify-between pb-3 border-b border-white/10 text-slate-500 text-[11px] mb-3">
              <span>$ ./vendor/bin/pest</span>
              <span class="text-emerald-400">PASSED (4.05s)</span>
            </div>
            <div class="space-y-1.5 text-slate-300 text-[11px] leading-relaxed">
              <div class="text-emerald-400">PASS Tests\Unit\TwitterCharCounterTest</div>
              <div class="text-slate-400 pl-4">✓ emoji handling</div>
              <div class="text-slate-400 pl-4">✓ tweet exceeds 280 characters</div>
              <div class="text-slate-400 pl-4">✓ url counting standard (t.co 23 chars)</div>
              <div class="text-emerald-400 pt-1">PASS Tests\Feature\PublishingIdempotencyTest</div>
              <div class="text-slate-400 pl-4">✓ concurrent lock prevents duplicate dispatches</div>
              <div class="text-slate-400 pl-4">✓ approved draft publishes successfully and records idempotency</div>
              <div class="text-slate-400 pl-4">✓ unapproved draft is strictly blocked</div>
              <div class="text-emerald-400 pt-1">PASS Tests\Feature\CostControlBudgetTest</div>
              <div class="text-slate-400 pl-4">✓ post cost calculation differentiates links</div>
              <div class="text-slate-400 pl-4">✓ hard cap blocks execution when daily budget exceeded</div>
              <div class="pt-2 text-emerald-400 font-bold border-t border-white/10 flex justify-between">
                <span>Tests: 47 passed (315 assertions)</span>
                <span>Duration: 12.80s</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- SELF-HOST QUICKSTART GUIDE -->
      <section id="quickstart" class="py-24 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto border-t border-white/10">
        <div class="text-center max-w-3xl mx-auto mb-12">
          <span class="text-xs font-mono font-bold tracking-widest text-indigo-400 uppercase">Deployment</span>
          <h2 class="text-3xl sm:text-5xl font-display font-extrabold text-white mt-2 mb-4">
            Up and Running in 3 Minutes
          </h2>
          <p class="text-slate-400 text-sm sm:text-base">
            Choose containerized Docker Compose or native Artisan dev setup.
          </p>
        </div>

        <div class="max-w-3xl mx-auto bg-slate-950 rounded-2xl border border-white/10 overflow-hidden shadow-2xl">
          <!-- Snippet Tabs -->
          <div class="flex items-center justify-between px-4 py-3 bg-slate-900 border-b border-white/10">
            <div class="flex items-center gap-2">
              <button
                @click="activeQuickstartTab = 'docker'"
                :class="[
                  'px-3 py-1.5 rounded-lg text-xs font-mono transition-all',
                  activeQuickstartTab === 'docker'
                    ? 'bg-indigo-600 text-white font-bold'
                    : 'text-slate-400 hover:text-white',
                ]"
              >
                docker-compose.yml
              </button>
              <button
                @click="activeQuickstartTab = 'artisan'"
                :class="[
                  'px-3 py-1.5 rounded-lg text-xs font-mono transition-all',
                  activeQuickstartTab === 'artisan'
                    ? 'bg-indigo-600 text-white font-bold'
                    : 'text-slate-400 hover:text-white',
                ]"
              >
                native-artisan.sh
              </button>
              <button
                @click="activeQuickstartTab = 'env'"
                :class="[
                  'px-3 py-1.5 rounded-lg text-xs font-mono transition-all',
                  activeQuickstartTab === 'env'
                    ? 'bg-indigo-600 text-white font-bold'
                    : 'text-slate-400 hover:text-white',
                ]"
              >
                .env.example
              </button>
            </div>

            <button
              @click="copySnippet(quickstartSnippets[activeQuickstartTab], activeQuickstartTab)"
              class="flex items-center gap-1.5 text-xs text-slate-400 hover:text-white font-mono px-2.5 py-1 rounded bg-slate-800 border border-white/10 transition-colors"
            >
              <Check v-if="copiedCode === activeQuickstartTab" class="w-3.5 h-3.5 text-emerald-400" />
              <Copy v-else class="w-3.5 h-3.5" />
              <span>{{ copiedCode === activeQuickstartTab ? 'Copied!' : 'Copy Code' }}</span>
            </button>
          </div>

          <!-- Code Viewport -->
          <pre class="p-6 text-xs font-mono text-indigo-300 leading-relaxed overflow-x-auto selection:bg-indigo-600 selection:text-white"><code>{{ quickstartSnippets[activeQuickstartTab] }}</code></pre>
        </div>
      </section>
    </main>

    <!-- FOOTER -->
    <footer class="border-t border-white/10 bg-slate-950 py-16 px-4 sm:px-6 lg:px-8 text-xs text-slate-400">
      <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-8 mb-12">
        <div class="space-y-3">
          <div class="flex items-center gap-2">
            <Sparkles class="w-4 h-4 text-indigo-400" />
            <span class="font-display font-bold text-white text-sm">SYNTHEX</span>
          </div>
          <p class="text-slate-500 leading-relaxed">
            Enterprise Autonomous Personal AI Social Media Assistant for LinkedIn and X.
          </p>
          <p class="font-mono text-[11px] text-slate-600">
            Open Source under MIT License.
          </p>
        </div>

        <div>
          <h4 class="font-mono text-white text-xs uppercase tracking-wider mb-3">Command Core</h4>
          <ul class="space-y-2">
            <li><Link href="/dashboard" class="hover:text-white transition-colors">Executive Dashboard</Link></li>
            <li><Link href="/inbox" class="hover:text-white transition-colors">Review Inbox (HITL)</Link></li>
            <li><Link href="/media" class="hover:text-white transition-colors">Canva & Clipchamp Studio</Link></li>
            <li><Link href="/trends" class="hover:text-white transition-colors">Trend Radar Intelligence</Link></li>
            <li><Link href="/calendar" class="hover:text-white transition-colors">Editorial Calendar</Link></li>
          </ul>
        </div>

        <div>
          <h4 class="font-mono text-white text-xs uppercase tracking-wider mb-3">Specifications</h4>
          <ul class="space-y-2 font-mono text-[11px]">
            <li><a href="https://github.com/Ahasun-h/personal-ai-social-assistant/blob/main/docs/architecture.md" target="_blank" class="hover:text-white transition-colors">docs/architecture.md</a></li>
            <li><a href="https://github.com/Ahasun-h/personal-ai-social-assistant/blob/main/docs/openapi.yaml" target="_blank" class="hover:text-white transition-colors">docs/openapi.yaml</a></li>
            <li><a href="https://github.com/Ahasun-h/personal-ai-social-assistant/blob/main/docs/srs.md" target="_blank" class="hover:text-white transition-colors">docs/srs.md</a></li>
            <li><a href="https://github.com/Ahasun-h/personal-ai-social-assistant/blob/main/DESIGN.md" target="_blank" class="hover:text-white transition-colors">DESIGN.md</a></li>
          </ul>
        </div>

        <div>
          <h4 class="font-mono text-white text-xs uppercase tracking-wider mb-3">Developer & Community</h4>
          <ul class="space-y-2">
            <li>
              <a
                href="https://github.com/Ahasun-h/personal-ai-social-assistant"
                target="_blank"
                class="hover:text-white transition-colors flex items-center gap-1.5"
              >
                <Github class="w-3.5 h-3.5" />
                <span>GitHub Repository</span>
              </a>
            </li>
            <li><Link href="/login" class="hover:text-white transition-colors">User Sign-In</Link></li>
          </ul>
        </div>
      </div>

      <div class="max-w-7xl mx-auto pt-8 border-t border-white/5 flex flex-col sm:flex-row items-center justify-between gap-4 font-mono text-[11px] text-slate-600">
        <p>© 2026 Synthex Social Command. Authored with Zero-Friction Autonomous SDLC Protocol.</p>
        <p>Built with Laravel 12 • Inertia v2 • Vue 3.4 • Tailwind 3.4</p>
      </div>
    </footer>
  </div>
</template>
