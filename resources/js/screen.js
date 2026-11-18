/**
 * LED winners screen.
 *
 * Opens on the animated event logo. When the operator presses Enter (or a
 * presenter clicker's Page Down / → key), the next prize is drawn: the logo
 * flies to the side, the prize is announced, ticket numbers roll and slow
 * down onto the winning ticket, and the winner is revealed with confetti.
 * Draws made from the admin panel are picked up by polling and animated too.
 */

const POLL_INTERVAL_MS = 1500;
const PRIZE_INTRO_MS = 1800;
const ROLL_MS = 6500;
const GRAND_PRIZE_ROLL_MS = 10000;
const ROLL_SLOWDOWN_MS = 460;
const GRAND_PRIZE_ROLL_SLOWDOWN_MS = 900;
const GRAND_PRIZE_FIREWORKS_MS = 9000;
const GRAND_PRIZE_TITLE = 'Grand Prize';
const GRAND_PRIZE_WINNER_LABEL = '★ Grand prize winner ★';
const REVEAL_HOLD_MS = 3500;
const GRAND_PRIZE_REVEAL_HOLD_MS = 9000;
const LOGO_FLIGHT_MS = 1100;
const EASE_OUT = 'cubic-bezier(0.2, 0.8, 0.2, 1)';
const EASE_IN_OUT = 'cubic-bezier(0.65, 0, 0.35, 1)';
const EASE_OUT_BACK = 'cubic-bezier(0.2, 0.9, 0.3, 1.35)';
const PLACEHOLDER_TICKET = '? ? ? ?';
const DRAW_KEYS = ['Enter', ' ', 'PageDown', 'ArrowRight'];
const LOGO_KEYS = ['l', 'L', 'Home'];

let confettiFrame = null;
let fireworksFrame = null;

const stage = document.querySelector('[data-stage]');

if (stage) {
    startScreen(stage);
}

function startScreen(stage) {
    const ui = {
        hero: stage.querySelector('[data-hero]'),
        introCaption: stage.querySelector('[data-intro-caption]'),
        sideLogo: stage.querySelector('[data-side-logo]'),
        main: stage.querySelector('[data-main]'),
        label: stage.querySelector('[data-label]'),
        rank: stage.querySelector('[data-rank]'),
        prize: stage.querySelector('[data-prize]'),
        ticketFrame: stage.querySelector('[data-ticket-frame]'),
        ticket: stage.querySelector('[data-ticket]'),
        winner: stage.querySelector('[data-winner]'),
        board: stage.querySelector('[data-board]'),
        boardPanel: stage.querySelector('[data-board-panel]'),
        boardSparks: stage.querySelector('[data-board-sparks]'),
        progress: stage.querySelector('[data-progress]'),
        progressBar: stage.querySelector('[data-progress-bar]'),
        totalEntries: stage.querySelector('[data-total-entries]'),
        flash: stage.querySelector('[data-flash]'),
        confetti: stage.querySelector('[data-confetti]'),
        sparkles: stage.querySelector('[data-sparkles]'),
        toast: stage.querySelector('[data-toast]'),
        rays: stage.querySelector('[data-rays]'),
        rings: stage.querySelector('[data-rings]'),
        fireworks: stage.querySelector('[data-fireworks]'),
        grand: stage.querySelector('[data-grand]'),
        grandEyebrow: stage.querySelector('[data-grand-eyebrow]'),
        grandTitle: stage.querySelector('[data-grand-title]'),
        grandPrizeName: stage.querySelector('[data-grand-prize-name]'),
    };

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const numberFormat = new Intl.NumberFormat();
    const pendingDraws = [];
    let lastSeenDrawId; // undefined until the first successful poll
    let latestState = null;
    let restingSignature = null;
    let lastDrawResponseAt = 0;
    let isPlaying = false;
    let isDrawRequestInFlight = false;
    let isChangingScene = false;

    addSparkles(ui.sparkles);
    keepScreenAwake();
    playLogoEntrance();

    document.addEventListener('dblclick', toggleFullscreen);
    document.addEventListener('keydown', (event) => {
        if (event.repeat) {
            return;
        }

        if (DRAW_KEYS.includes(event.key)) {
            event.preventDefault();
            requestDraw();
        } else if (LOGO_KEYS.includes(event.key)) {
            showLogoScene();
        }
    });

    poll();
    setInterval(poll, POLL_INTERVAL_MS);

    function isBusy() {
        return isPlaying || isDrawRequestInFlight || isChangingScene || pendingDraws.length > 0;
    }

    async function poll() {
        const requestedAt = performance.now();
        let state;

        try {
            const response = await fetch(stage.dataset.stateUrl, {
                headers: { Accept: 'application/json' },
                cache: 'no-store',
            });

            if (response.status === 401 || response.status === 419) {
                window.location.reload(); // Signed out: show the login page.

                return;
            }

            if (! response.ok) {
                return;
            }

            state = await response.json();
        } catch {
            return; // Keep showing the last known state through network blips.
        }

        // A poll that started before this screen's own draw returned is out of date.
        if (requestedAt < lastDrawResponseAt) {
            return;
        }

        latestState = state;
        const latestDrawId = state.latest_draw?.id ?? null;

        if (lastSeenDrawId === undefined || latestDrawId === null) {
            lastSeenDrawId = latestDrawId;
        } else if (lastSeenDrawId === null || latestDrawId > lastSeenDrawId) {
            lastSeenDrawId = latestDrawId; // Drawn from the admin panel.
            queueDraw(state);
        }

        if (! isBusy()) {
            renderResting(state);
        }
    }

    async function requestDraw() {
        if (isBusy()) {
            return;
        }

        isDrawRequestInFlight = true;

        try {
            const response = await fetch(stage.dataset.drawUrl, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
                cache: 'no-store',
            });

            if (response.status === 401 || response.status === 419) {
                window.location.reload();

                return;
            }

            const body = await response.json();

            if (! response.ok) {
                showToast(body.message ?? 'The draw could not be made.');

                return;
            }

            lastDrawResponseAt = performance.now();
            latestState = body;
            lastSeenDrawId = body.latest_draw?.id ?? null;
            queueDraw(body);
        } catch {
            showToast('Could not reach the server. Please try again.');
        } finally {
            isDrawRequestInFlight = false;
        }
    }

    function queueDraw(state) {
        pendingDraws.push(state);
        playPendingDraws();
    }

    async function playPendingDraws() {
        if (isPlaying) {
            return;
        }

        isPlaying = true;

        while (pendingDraws.length > 0) {
            await playDraw(pendingDraws.shift());
        }

        isPlaying = false;
        renderResting(latestState);
    }

    /**
     * Logo scene: the logo zooms in with a glow, and a light sweeps across it.
     */
    function playLogoEntrance() {
        ui.hero.animate(
            [
                { opacity: 0, transform: 'scale(0.7)', filter: 'blur(1.5cqw) brightness(2)' },
                { opacity: 1, transform: 'scale(1.03)', filter: 'blur(0) brightness(1.3)', offset: 0.7 },
                { opacity: 1, transform: 'scale(1)', filter: 'blur(0) brightness(1)' },
            ],
            { duration: 1800, easing: EASE_OUT },
        );
        ui.introCaption.animate(
            [
                { opacity: 0, transform: 'translateY(60%)', letterSpacing: '0.8em' },
                { opacity: 1, transform: 'none', letterSpacing: '0.3em' },
            ],
            { duration: 1400, delay: 900, easing: EASE_OUT, fill: 'backwards' },
        );
        ui.flash.animate(
            [{ opacity: 0 }, { opacity: 0.45, offset: 0.3 }, { opacity: 0 }],
            { duration: 1600, delay: 300, easing: 'ease-out' },
        );
    }

    async function showLogoScene() {
        if (isBusy() || stage.dataset.scene === 'intro') {
            return;
        }

        isChangingScene = true;
        stage.dataset.scene = 'intro';
        ui.sideLogo.style.opacity = '0';
        ui.hero.style.visibility = '';
        playLogoEntrance();
        await sleep(1000);
        isChangingScene = false;
    }

    /**
     * Fly the big logo into its place on the left and fade the draw layout in.
     */
    async function showDrawScene() {
        if (stage.dataset.scene === 'draw') {
            return;
        }

        const from = ui.hero.getBoundingClientRect();
        const to = ui.sideLogo.getBoundingClientRect();
        const offsetX = to.left + to.width / 2 - (from.left + from.width / 2);
        const offsetY = to.top + to.height / 2 - (from.top + from.height / 2);
        const scale = to.width / from.width;

        stage.dataset.scene = 'draw';
        playBoardEntrance();

        const flight = ui.hero.animate(
            [
                { transform: 'none' },
                { transform: `translate(${offsetX}px, ${offsetY}px) scale(${scale})` },
            ],
            { duration: LOGO_FLIGHT_MS, easing: EASE_IN_OUT, fill: 'forwards' },
        );

        await flight.finished;

        ui.sideLogo.style.opacity = '1';
        ui.hero.style.visibility = 'hidden';
        flight.cancel();
    }

    /**
     * Show the current state without animation: the latest winner, or the next prize.
     */
    function renderResting(state) {
        if (! state) {
            return;
        }

        const signature = JSON.stringify([state.prizes, state.latest_draw?.id, state.total_entries]);

        if (signature === restingSignature) {
            return;
        }

        restingSignature = signature;
        ui.totalEntries.textContent = numberFormat.format(state.total_entries);

        const latestPrize = findPrize(state, state.latest_draw?.prize_id);
        const nextPrize = state.prizes.find((prize) => ! prize.winner);

        if (latestPrize?.winner) {
            renderBoard(state.prizes, { activePrizeId: latestPrize.id });
            showStage('revealed', {
                label: latestPrize.is_grand_prize ? GRAND_PRIZE_WINNER_LABEL : 'Winner',
                prize: latestPrize,
                prizes: state.prizes,
                ticket: latestPrize.winner.ticket_number,
                winner: latestPrize.winner.name,
            });

            return;
        }

        renderBoard(state.prizes, { activePrizeId: nextPrize?.id });
        showStage('idle', {
            label: nextPrize ? 'Up next' : '',
            prize: nextPrize,
            prizes: state.prizes,
            ticket: PLACEHOLDER_TICKET,
            winner: '',
        });
    }

    async function playDraw(state) {
        restingSignature = null;

        const prize = findPrize(state, state.latest_draw.prize_id);

        if (! prize?.winner) {
            return;
        }

        const isGrandPrize = prize.is_grand_prize;
        ui.totalEntries.textContent = numberFormat.format(state.total_entries);

        // 1. Announce the prize, keeping its winner off the board for now.
        renderBoard(state.prizes, { activePrizeId: prize.id, hiddenPrizeId: prize.id });
        showStage('drawing', {
            label: isGrandPrize ? 'Drawing the grand prize' : 'Now drawing',
            prize,
            prizes: state.prizes,
            ticket: '',
            winner: '',
        });

        if (isGrandPrize) {
            await playGrandPrizeIntro(prize);
            showDrawSceneInstantly();
            await hideGrandPrizeIntro();
        } else if (stage.dataset.scene !== 'draw') {
            await showDrawScene();
        }

        ui.rank.animate(
            [{ opacity: 0 }, { opacity: 1 }],
            { duration: 600, easing: EASE_OUT },
        );
        ui.prize.animate(
            [
                { opacity: 0, transform: 'translateY(30%) scale(0.96)', filter: 'blur(0.5cqw)' },
                { opacity: 1, transform: 'none', filter: 'blur(0)' },
            ],
            { duration: 900, easing: EASE_OUT },
        );
        await sleep(PRIZE_INTRO_MS);

        // 2. Spin through entries (ticket number, name underneath), slowing down onto the winner.
        const entryPool = uniqueByTicket([...state.roll_entries, prize.winner]);
        const suspense = isGrandPrize ? startGrandPrizeSuspense() : null;
        await rollEntries(
            entryPool,
            prize.winner,
            isGrandPrize ? GRAND_PRIZE_ROLL_MS : ROLL_MS,
            isGrandPrize ? GRAND_PRIZE_ROLL_SLOWDOWN_MS : ROLL_SLOWDOWN_MS,
        );
        suspense?.stop();

        // 3. Reveal the winner.
        ui.main.dataset.mode = 'revealed';
        ui.label.textContent = isGrandPrize ? GRAND_PRIZE_WINNER_LABEL : 'Winner';
        setWinner(prize.winner.name);

        ui.label.animate(
            [{ opacity: 0, letterSpacing: '0.8em' }, { opacity: 1, letterSpacing: '0.2em' }],
            { duration: 900, easing: EASE_OUT },
        );

        if (isGrandPrize) {
            playGrandPrizeReveal();
        } else {
            flashStage(0.9, 1400);
            ui.ticketFrame.animate(
                [{ transform: 'scale(1)' }, { transform: 'scale(1.1)' }, { transform: 'scale(1)' }],
                { duration: 800, easing: EASE_OUT_BACK },
            );
            ui.winner.animate(
                [
                    { opacity: 0, transform: 'translateY(40%)', filter: 'blur(0.5cqw)' },
                    { opacity: 1, transform: 'none', filter: 'blur(0)' },
                ],
                { duration: 1000, delay: 300, easing: EASE_OUT, fill: 'backwards' },
            );
            launchConfetti(ui.confetti, { isGrandPrize: false });
        }

        await sleep(700);
        renderBoard(state.prizes, { activePrizeId: prize.id, poppedPrizeId: prize.id, isCelebratingGrandPrize: isGrandPrize });

        await sleep(isGrandPrize ? GRAND_PRIZE_REVEAL_HOLD_MS : REVEAL_HOLD_MS);
    }

    /**
     * Grand prize takeover: dim the wall, sweep spotlights, drop in "Grand Prize" letter by
     * letter, then hit it with a flash, a shake and shockwave rings before naming the prize.
     */
    async function playGrandPrizeIntro(prize) {
        ui.grandPrizeName.textContent = prize.name;
        ui.grandTitle.replaceChildren(
            ...[...GRAND_PRIZE_TITLE].map((character) => (character === ' '
                ? element('span', 'inline-block w-[0.3em]', ' ')
                : element('span', 'screen-grand-letter inline-block opacity-0', character))),
        );
        ui.grand.style.visibility = 'visible';

        await ui.grand.animate(
            [{ opacity: 0 }, { opacity: 1 }],
            { duration: 800, easing: 'ease-out', fill: 'forwards' },
        ).finished;

        ui.grandEyebrow.animate(
            [{ opacity: 0, letterSpacing: '1em' }, { opacity: 1, letterSpacing: '0.5em' }],
            { duration: 1200, easing: EASE_OUT, fill: 'both' },
        );
        await sleep(900);

        const letters = [...ui.grandTitle.querySelectorAll('.screen-grand-letter')];
        letters.forEach((letter, index) => {
            letter.animate(
                [
                    { opacity: 0, transform: 'translateY(-80%) scale(1.8)', filter: 'blur(0.6cqw)' },
                    { opacity: 1, transform: 'none', filter: 'blur(0)' },
                ],
                { duration: 650, delay: index * 90, easing: EASE_OUT_BACK, fill: 'both' },
            );
        });
        await sleep(letters.length * 90 + 450);

        flashStage(0.8, 1200);
        shakeStage();
        spawnRings(null, 3);
        ui.grandTitle.animate(
            [{ filter: 'brightness(1)' }, { filter: 'brightness(1.9)' }, { filter: 'brightness(1)' }],
            { duration: 900, easing: 'ease-out' },
        );
        ui.grandPrizeName.animate(
            [{ opacity: 0, transform: 'translateY(40%)' }, { opacity: 1, transform: 'none' }],
            { duration: 900, delay: 400, easing: EASE_OUT, fill: 'both' },
        );
        await sleep(3000);
    }

    /**
     * Zoom the takeover away to uncover the draw layout, then reset it for next time.
     */
    async function hideGrandPrizeIntro() {
        await ui.grand.animate(
            [{ opacity: 1, transform: 'scale(1)' }, { opacity: 0, transform: 'scale(1.2)' }],
            { duration: 900, easing: EASE_IN_OUT, fill: 'forwards' },
        ).finished;

        ui.grand.style.visibility = '';
        ui.grand.getAnimations({ subtree: true })
            .filter((animation) => ! (animation instanceof CSSAnimation))
            .forEach((animation) => animation.cancel());
    }

    /**
     * Switch to the draw layout without the logo flight (it happens under the grand prize takeover).
     */
    function showDrawSceneInstantly() {
        if (stage.dataset.scene === 'draw') {
            return;
        }

        stage.dataset.scene = 'draw';
        ui.hero.style.visibility = 'hidden';
        ui.sideLogo.style.opacity = '1';
        playBoardEntrance();
    }

    /**
     * While the grand prize rolls: the ticket frame beats like a heart and the light rays spin faster.
     */
    function startGrandPrizeSuspense() {
        const heartbeat = ui.ticketFrame.animate(
            [
                { transform: 'scale(1)' },
                { transform: 'scale(1.035)', offset: 0.15 },
                { transform: 'scale(1)', offset: 0.3 },
                { transform: 'scale(1.02)', offset: 0.45 },
                { transform: 'scale(1)', offset: 0.6 },
            ],
            { duration: 1000, iterations: Infinity },
        );
        const rays = ui.rays.getAnimations()[0];
        rays?.updatePlaybackRate(8);

        return {
            stop() {
                heartbeat.cancel();
                rays?.updatePlaybackRate(1);
            },
        };
    }

    function playGrandPrizeReveal() {
        flashStage(1, 1800);
        shakeStage();
        spawnRings(ui.ticketFrame, 4);
        ui.ticketFrame.animate(
            [{ transform: 'scale(1)' }, { transform: 'scale(1.16)' }, { transform: 'scale(1)' }],
            { duration: 1000, easing: EASE_OUT_BACK },
        );
        ui.winner.animate(
            [
                { opacity: 0, transform: 'scale(0.4)', filter: 'blur(1cqw)' },
                { opacity: 1, transform: 'scale(1.1)', filter: 'blur(0)', offset: 0.7 },
                { opacity: 1, transform: 'none', filter: 'blur(0)' },
            ],
            { duration: 1300, delay: 300, easing: EASE_OUT, fill: 'backwards' },
        );
        launchConfetti(ui.confetti, { isGrandPrize: true });
        launchFireworks(ui.fireworks, GRAND_PRIZE_FIREWORKS_MS);
    }

    function flashStage(peakOpacity, durationMs) {
        ui.flash.animate(
            [{ opacity: 0 }, { opacity: peakOpacity, offset: 0.15 }, { opacity: 0 }],
            { duration: durationMs, easing: 'ease-out' },
        );
    }

    function shakeStage() {
        stage.animate(
            [
                { transform: 'translate(0, 0)' },
                { transform: 'translate(-0.4%, 0.3%)' },
                { transform: 'translate(0.4%, -0.3%)' },
                { transform: 'translate(-0.3%, -0.2%)' },
                { transform: 'translate(0.2%, 0.3%)' },
                { transform: 'translate(0, 0)' },
            ],
            { duration: 600, easing: 'ease-out' },
        );
    }

    /**
     * Gold shockwave rings expanding from an element's centre (or the stage centre).
     */
    function spawnRings(target, count) {
        const stageRect = stage.getBoundingClientRect();
        const targetRect = target ? target.getBoundingClientRect() : stageRect;
        const centerX = targetRect.left + targetRect.width / 2 - stageRect.left;
        const centerY = targetRect.top + targetRect.height / 2 - stageRect.top;

        for (let index = 0; index < count; index++) {
            const ring = element('span', 'screen-ring');
            ring.style.left = `${centerX}px`;
            ring.style.top = `${centerY}px`;
            ui.rings.append(ring);

            ring.animate(
                [
                    { transform: 'translate(-50%, -50%) scale(0.2)', opacity: 1 },
                    { transform: 'translate(-50%, -50%) scale(7)', opacity: 0 },
                ],
                { duration: 1800, delay: index * 280, easing: 'cubic-bezier(0.1, 0.6, 0.3, 1)', fill: 'backwards' },
            ).finished.then(() => ring.remove());
        }
    }

    /**
     * Roll through entries, slowing down: the gap between ticks grows towards slowdownMs.
     */
    async function rollEntries(pool, winningEntry, durationMs, slowdownMs) {
        const startedAt = performance.now();
        let previousEntry = null;

        for (;;) {
            const progress = (performance.now() - startedAt) / durationMs;

            if (progress >= 1) {
                break;
            }

            const entry = pickDifferent(pool, previousEntry);
            const delayMs = 40 + slowdownMs * progress ** 3;
            previousEntry = entry;

            showRollingEntry(entry);
            flipRollingEntry(Math.min(delayMs, 180));
            await sleep(delayMs);
        }

        showRollingEntry(winningEntry);
        flipRollingEntry(320);
    }

    /**
     * While rolling, the name sits small under the ticket number; the reveal enlarges it.
     */
    function showRollingEntry(entry) {
        setTicket(entry.ticket_number);
        ui.winner.textContent = entry.name;
        ui.winner.style.fontSize = '2.2cqw';
    }

    function flipRollingEntry(durationMs) {
        const keyframes = [
            { transform: 'translateY(-60%)', opacity: 0.2, filter: 'blur(0.35cqw)' },
            { transform: 'none', opacity: 1, filter: 'blur(0)' },
        ];

        ui.ticket.animate(keyframes, { duration: durationMs, easing: 'ease-out' });
        ui.winner.animate(keyframes, { duration: durationMs, easing: 'ease-out' });
    }

    function showToast(message) {
        ui.toast.textContent = message;
        ui.toast.animate(
            [
                { opacity: 0, transform: 'translateY(40%)' },
                { opacity: 1, transform: 'none', offset: 0.1 },
                { opacity: 1, transform: 'none', offset: 0.85 },
                { opacity: 0, transform: 'none' },
            ],
            { duration: 4000, easing: 'ease-out' },
        );
    }

    function showStage(mode, { label, prize, prizes, ticket, winner }) {
        ui.main.dataset.mode = mode;
        ui.main.dataset.grand = String(Boolean(prize?.is_grand_prize));
        ui.label.textContent = label;
        ui.rank.textContent = prize ? `Prize ${prize.rank} of ${prizes.length}` : '';
        setPrize(prize?.name ?? (prizes.length ? 'All prizes have been drawn' : 'Prizes coming soon'));
        setTicket(ticket);
        setWinner(winner);
    }

    /**
     * Long prize names get a smaller size so they stay within two lines.
     */
    function setPrize(name) {
        ui.prize.textContent = name;
        ui.prize.style.fontSize = name.length <= 34 ? '3cqw' : name.length <= 52 ? '2.6cqw' : '2.2cqw';
    }

    /**
     * Shrink long ticket numbers so they always fit the ticket frame.
     */
    function setTicket(ticket) {
        ui.ticket.textContent = ticket;
        ui.ticket.style.fontSize = `${Math.min(6.2, 58 / Math.max(ticket.length, 4))}cqw`;
    }

    function setWinner(name) {
        ui.winner.textContent = name;
        ui.winner.style.fontSize = name.length <= 18 ? '4cqw' : name.length <= 28 ? '3.2cqw' : name.length <= 40 ? '2.6cqw' : '2.2cqw';
    }

    function renderBoard(prizes, { activePrizeId = null, hiddenPrizeId = null, poppedPrizeId = null, isCelebratingGrandPrize = false } = {}) {
        const drawnCount = prizes.filter((prize) => prize.winner && prize.id !== hiddenPrizeId).length;
        ui.progress.textContent = prizes.length ? `${drawnCount} of ${prizes.length} drawn` : '';
        ui.progressBar.style.width = `${prizes.length ? (drawnCount / prizes.length) * 100 : 0}%`;

        // Row sizes are in em, so shrinking the board's font size fits more than 10 prizes.
        ui.board.style.fontSize = `${Math.min(1.1, 11 / Math.max(prizes.length, 1))}cqw`;

        ui.board.replaceChildren(
            ...prizes.map((prize, index) => boardRow(prize, index, {
                winner: prize.id === hiddenPrizeId ? null : prize.winner,
                isActive: prize.id === activePrizeId,
            })),
        );

        if (poppedPrizeId !== null) {
            celebrateRow(ui.board.querySelector(`[data-prize-id="${poppedPrizeId}"]`), isCelebratingGrandPrize);
        }
    }

    /**
     * A new winner's row bursts with light, sparks fly from its badge and the name slides in.
     */
    function celebrateRow(row, isGrandPrize) {
        if (! row) {
            return;
        }

        row.animate(
            [
                { transform: 'scale(1)', backgroundColor: 'rgb(251 191 36 / 0.65)', boxShadow: '0 0 0 0 rgb(251 191 36 / 0.9)' },
                { transform: 'scale(1.05)', boxShadow: '0 0 3em 0.6em rgb(251 191 36 / 0.65)', offset: 0.3 },
                { transform: 'scale(1)', backgroundColor: 'rgb(251 191 36 / 0.12)', boxShadow: '0 0 1em 0 rgb(251 191 36 / 0.25)' },
            ],
            { duration: 1400, easing: EASE_OUT },
        );
        row.querySelector('[data-row-badge]')?.animate(
            [
                { transform: 'rotateY(180deg) scale(0.4)', opacity: 0 },
                { transform: 'rotateY(0deg) scale(1.25)', opacity: 1, offset: 0.6 },
                { transform: 'none', opacity: 1 },
            ],
            { duration: 900, easing: EASE_OUT },
        );
        row.querySelector('[data-row-details]')?.animate(
            [{ opacity: 0, transform: 'translateX(1.5em)' }, { opacity: 1, transform: 'none' }],
            { duration: 800, delay: 200, easing: EASE_OUT, fill: 'backwards' },
        );
        row.querySelector('[data-row-ticket]')?.animate(
            [{ opacity: 0, transform: 'scale(0.4)' }, { opacity: 1, transform: 'scale(1.2)', offset: 0.7 }, { opacity: 1, transform: 'none' }],
            { duration: 800, delay: 400, easing: EASE_OUT, fill: 'backwards' },
        );

        burstSparks(row.querySelector('[data-row-badge]') ?? row, isGrandPrize ? 36 : 18, isGrandPrize ? 0.3 : 0.18);
    }

    function burstSparks(origin, count, reachRatio) {
        const panelRect = ui.boardPanel.getBoundingClientRect();
        const originRect = origin.getBoundingClientRect();
        const centerX = originRect.left + originRect.width / 2 - panelRect.left;
        const centerY = originRect.top + originRect.height / 2 - panelRect.top;
        const reach = panelRect.width * reachRatio;

        for (let index = 0; index < count; index++) {
            const spark = document.createElement('span');
            const size = panelRect.width * (0.006 + Math.random() * 0.008);
            const angle = (index / count) * Math.PI * 2 + Math.random() * 0.3;
            const distance = reach * (0.5 + Math.random() * 0.6);

            spark.className = 'screen-spark';
            spark.style.left = `${centerX - size / 2}px`;
            spark.style.top = `${centerY - size / 2}px`;
            spark.style.width = `${size}px`;
            spark.style.height = `${size}px`;
            ui.boardSparks.append(spark);

            spark.animate(
                [
                    { transform: 'translate(0, 0) scale(1)', opacity: 1 },
                    { transform: `translate(${Math.cos(angle) * distance}px, ${Math.sin(angle) * distance}px) scale(0.2)`, opacity: 0 },
                ],
                { duration: 900 + Math.random() * 500, easing: 'cubic-bezier(0.1, 0.7, 0.3, 1)' },
            ).finished.then(() => spark.remove());
        }
    }

    /**
     * When the draw layout appears, the board slides in and its rows cascade in.
     */
    function playBoardEntrance() {
        ui.boardPanel.animate(
            [{ opacity: 0, transform: 'translateX(12%)' }, { opacity: 1, transform: 'none' }],
            { duration: 900, delay: 250, easing: EASE_OUT, fill: 'backwards' },
        );

        [...ui.board.children].forEach((row, index) => {
            row.animate(
                [{ opacity: 0, transform: 'translateX(25%)' }, { opacity: 1, transform: 'none' }],
                { duration: 600, delay: 500 + index * 70, easing: EASE_OUT, fill: 'backwards' },
            );
        });
    }

    function boardRow(prize, index, { winner, isActive }) {
        const row = element('li', classNames(
            'screen-row flex items-center gap-[0.9em] rounded-[0.7em] px-[0.7em] py-[0.15em]',
            winner && 'screen-row-winner',
            isActive && 'screen-row-active',
        ));
        row.dataset.prizeId = prize.id;
        row.style.setProperty('--row-index', index);

        const badge = element('span', classNames(
            'grid size-[2em] shrink-0 place-items-center rounded-full text-[0.85em] font-bold tabular-nums',
            winner ? 'screen-badge-winner bg-amber-300 text-stone-950' : 'bg-white/10 text-white/60',
            prize.is_grand_prize && ! winner && 'screen-badge-grand text-amber-200 ring-[0.1em] ring-amber-300/70',
        ), prize.is_grand_prize ? '★' : String(prize.rank));
        badge.dataset.rowBadge = '';

        const details = element('div', 'min-w-0 flex-1');
        details.dataset.rowDetails = '';
        details.append(
            element('p', 'truncate text-[0.75em] leading-tight text-white/55', prize.name),
            element('p', classNames(
                'truncate text-[1em] leading-tight font-bold',
                winner ? 'text-white' : 'text-white/25',
            ), winner ? winner.name : '—'),
        );

        row.append(badge, details);

        if (winner) {
            const ticket = element('span', 'screen-row-ticket shrink-0 text-[0.95em] font-bold text-amber-300 tabular-nums', winner.ticket_number);
            ticket.dataset.rowTicket = '';
            row.append(ticket);
        }

        return row;
    }
}

/**
 * Burst confetti from the bottom corners (and more places for the grand prize).
 */
function launchConfetti(canvas, { isGrandPrize }) {
    const context = canvas.getContext('2d');
    const pixelRatio = window.devicePixelRatio || 1;
    const width = canvas.clientWidth;
    const height = canvas.clientHeight;

    canvas.width = Math.round(width * pixelRatio);
    canvas.height = Math.round(height * pixelRatio);
    context.setTransform(pixelRatio, 0, 0, pixelRatio, 0, 0);

    const colors = ['#fde68a', '#fbbf24', '#f59e0b', '#ffffff', '#fb7185', '#c4b5fd'];
    const bursts = [
        { x: 0.02, y: 1.02, angle: -62 },
        { x: 0.98, y: 1.02, angle: -118 },
        ...(isGrandPrize ? [
            { x: 0.5, y: 1.02, angle: -90 },
            { x: 0.02, y: 0.55, angle: -35 },
            { x: 0.98, y: 0.55, angle: -145 },
        ] : []),
    ];
    const particlesPerBurst = isGrandPrize ? 220 : 170;
    const durationMs = isGrandPrize ? 7500 : 5000;

    const particles = bursts.flatMap((burst) => Array.from({ length: particlesPerBurst }, () => {
        const angle = ((burst.angle + (Math.random() - 0.5) * 50) * Math.PI) / 180;
        const speed = height * (0.022 + Math.random() * 0.03);

        return {
            x: burst.x * width,
            y: burst.y * height,
            velocityX: Math.cos(angle) * speed,
            velocityY: Math.sin(angle) * speed,
            size: width * (0.004 + Math.random() * 0.006),
            color: colors[Math.floor(Math.random() * colors.length)],
            rotation: Math.random() * Math.PI * 2,
            spin: (Math.random() - 0.5) * 0.3,
            tilt: Math.random() * Math.PI * 2,
            tiltSpeed: 0.05 + Math.random() * 0.12,
            isRound: Math.random() < 0.25,
        };
    }));

    const gravity = height * 0.00075;
    const startedAt = performance.now();
    let previousFrameAt = startedAt;

    cancelAnimationFrame(confettiFrame);

    const drawFrame = (now) => {
        const elapsed = now - startedAt;
        const step = Math.min((now - previousFrameAt) / 16.67, 3); // keep speed steady on any refresh rate
        previousFrameAt = now;

        context.clearRect(0, 0, width, height);
        context.globalAlpha = Math.max(0, Math.min(1, (durationMs - elapsed) / (durationMs * 0.35)));

        for (const particle of particles) {
            particle.velocityX *= 0.985 ** step;
            particle.velocityY = particle.velocityY * 0.985 ** step + gravity * step;
            particle.x += particle.velocityX * step;
            particle.y += particle.velocityY * step;
            particle.rotation += particle.spin * step;
            particle.tilt += particle.tiltSpeed * step;

            context.save();
            context.translate(particle.x, particle.y);
            context.rotate(particle.rotation);
            context.fillStyle = particle.color;

            if (particle.isRound) {
                context.beginPath();
                context.arc(0, 0, particle.size * 0.4, 0, Math.PI * 2);
                context.fill();
            } else {
                const flipHeight = Math.max(particle.size * 0.6 * Math.abs(Math.cos(particle.tilt)), 0.5);
                context.fillRect(-particle.size / 2, -flipHeight / 2, particle.size, flipHeight);
            }

            context.restore();
        }

        if (elapsed < durationMs) {
            confettiFrame = requestAnimationFrame(drawFrame);
        } else {
            context.clearRect(0, 0, width, height);
        }
    };

    confettiFrame = requestAnimationFrame(drawFrame);
}

/**
 * Fireworks for the grand prize: rockets rise from the bottom and burst into glowing sparks.
 */
function launchFireworks(canvas, durationMs) {
    const context = canvas.getContext('2d');
    const pixelRatio = window.devicePixelRatio || 1;
    const width = canvas.clientWidth;
    const height = canvas.clientHeight;

    canvas.width = Math.round(width * pixelRatio);
    canvas.height = Math.round(height * pixelRatio);
    context.setTransform(pixelRatio, 0, 0, pixelRatio, 0, 0);

    const palettes = [
        ['#fde68a', '#fbbf24', '#ffffff'],
        ['#fff7d6', '#f59e0b', '#fde68a'],
        ['#fecaca', '#fb7185', '#ffffff'],
        ['#ddd6fe', '#a78bfa', '#ffffff'],
    ];
    const rockets = [];
    const sparks = [];
    const gravity = height * 0.00035;
    const startedAt = performance.now();
    let previousFrameAt = startedAt;
    let nextLaunchAt = startedAt;

    const launchRocket = () => rockets.push({
        x: width * (0.12 + Math.random() * 0.76),
        y: height,
        velocityX: (Math.random() - 0.5) * width * 0.002,
        velocityY: -height * (0.022 + Math.random() * 0.008),
        palette: palettes[Math.floor(Math.random() * palettes.length)],
        burstHeight: height * (0.15 + Math.random() * 0.35),
    });

    const explode = (rocket) => {
        const sparkCount = 90;

        for (let index = 0; index < sparkCount; index++) {
            const angle = (index / sparkCount) * Math.PI * 2 + Math.random() * 0.1;
            const speed = height * (0.004 + Math.random() * 0.01);

            sparks.push({
                x: rocket.x,
                y: rocket.y,
                velocityX: Math.cos(angle) * speed,
                velocityY: Math.sin(angle) * speed,
                color: rocket.palette[Math.floor(Math.random() * rocket.palette.length)],
                life: 1,
                decay: 0.008 + Math.random() * 0.008,
                size: width * (0.0018 + Math.random() * 0.002),
            });
        }
    };

    cancelAnimationFrame(fireworksFrame);

    const drawFrame = (now) => {
        const elapsed = now - startedAt;
        const step = Math.min((now - previousFrameAt) / 16.67, 3);
        previousFrameAt = now;

        context.globalCompositeOperation = 'source-over';
        context.clearRect(0, 0, width, height);
        context.globalCompositeOperation = 'lighter';

        if (elapsed < durationMs - 1500 && now >= nextLaunchAt) {
            launchRocket();

            if (Math.random() < 0.4) {
                launchRocket();
            }

            nextLaunchAt = now + 280 + Math.random() * 420;
        }

        for (let index = rockets.length - 1; index >= 0; index--) {
            const rocket = rockets[index];
            rocket.velocityY += gravity * step;
            rocket.x += rocket.velocityX * step;
            rocket.y += rocket.velocityY * step;

            context.strokeStyle = 'rgb(253 230 138 / 0.9)';
            context.lineWidth = width * 0.0015;
            context.beginPath();
            context.moveTo(rocket.x, rocket.y);
            context.lineTo(rocket.x - rocket.velocityX * 3, rocket.y - rocket.velocityY * 3);
            context.stroke();

            if (rocket.y <= rocket.burstHeight || rocket.velocityY >= 0) {
                explode(rocket);
                rockets.splice(index, 1);
            }
        }

        for (let index = sparks.length - 1; index >= 0; index--) {
            const spark = sparks[index];
            spark.velocityX *= 0.97 ** step;
            spark.velocityY = spark.velocityY * 0.97 ** step + gravity * 0.6 * step;
            spark.x += spark.velocityX * step;
            spark.y += spark.velocityY * step;
            spark.life -= spark.decay * step;

            if (spark.life <= 0) {
                sparks.splice(index, 1);
                continue;
            }

            context.globalAlpha = spark.life;
            context.fillStyle = spark.color;
            context.beginPath();
            context.arc(spark.x, spark.y, spark.size, 0, Math.PI * 2);
            context.fill();
        }

        context.globalAlpha = 1;

        if (elapsed < durationMs || rockets.length > 0 || sparks.length > 0) {
            fireworksFrame = requestAnimationFrame(drawFrame);
        } else {
            context.clearRect(0, 0, width, height);
        }
    };

    fireworksFrame = requestAnimationFrame(drawFrame);
}

function addSparkles(container, count = 46) {
    for (let index = 0; index < count; index++) {
        const sparkle = document.createElement('span');
        const size = 0.12 + Math.random() * 0.28;

        sparkle.className = 'screen-sparkle';
        sparkle.style.left = `${Math.random() * 100}%`;
        sparkle.style.top = `${Math.random() * 100}%`;
        sparkle.style.width = `${size}cqw`;
        sparkle.style.height = `${size}cqw`;
        sparkle.style.setProperty('--twinkle-duration', `${2.5 + Math.random() * 4}s`);
        sparkle.style.setProperty('--twinkle-delay', `${-Math.random() * 6}s`);

        container.append(sparkle);
    }
}

function keepScreenAwake() {
    if (! ('wakeLock' in navigator)) {
        return;
    }

    const requestWakeLock = () => navigator.wakeLock.request('screen').catch(() => {});

    requestWakeLock();
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            requestWakeLock();
        }
    });
}

function toggleFullscreen() {
    if (document.fullscreenElement) {
        document.exitFullscreen();
    } else {
        document.documentElement.requestFullscreen?.().catch(() => {});
    }
}

function findPrize(state, prizeId) {
    return state.prizes.find((prize) => prize.id === prizeId);
}

function uniqueByTicket(entries) {
    return [...new Map(entries.map((entry) => [entry.ticket_number, entry])).values()];
}

function pickDifferent(pool, previous) {
    if (pool.length < 2) {
        return pool[0];
    }

    let candidate;

    do {
        candidate = pool[Math.floor(Math.random() * pool.length)];
    } while (candidate === previous);

    return candidate;
}

/**
 * Create an element with text set via textContent, so entrant names are never parsed as HTML.
 */
function element(tagName, className, text = null) {
    const node = document.createElement(tagName);
    node.className = className;

    if (text !== null) {
        node.textContent = text;
    }

    return node;
}

function classNames(...classes) {
    return classes.filter(Boolean).join(' ');
}

function sleep(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}
