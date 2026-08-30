<?php

declare(strict_types=1);

namespace Src\Presentation\Http\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Application\Campaign\ViewCampaign\ViewCampaignHandler;
use Src\Application\Campaign\ViewCampaign\ViewCampaignQuery;
use Src\Application\Leaderboard\ViewLeaderboard\ViewLeaderboardHandler;
use Src\Application\Leaderboard\ViewLeaderboard\ViewLeaderboardQuery;
use Src\Application\Participation\MyRewards\MyRewardsHandler;
use Src\Application\Participation\MyRewards\MyRewardsQuery;
use Src\Application\Participation\OpenSession\OpenSessionCommand;
use Src\Application\Participation\OpenSession\OpenSessionHandler;
use Src\Application\Participation\PlayAttempt\PlayAttemptCommand;
use Src\Application\Participation\PlayAttempt\PlayAttemptHandler;
use Src\Presentation\Http\Middleware\ResolveParticipant;
use Src\Presentation\Http\Request\OpenSessionRequest;
use Src\Presentation\Http\Request\PlayAttemptRequest;
use Src\Presentation\Http\SnakeArray;

final class PublicController
{
    public function showCampaign(Request $request, string $slug, ViewCampaignHandler $handler): JsonResponse
    {
        $view = $handler->handle(new ViewCampaignQuery($slug, $this->optionalParticipant($request)));

        return response()->json(['data' => SnakeArray::from((array) $view)]);
    }

    public function leaderboard(Request $request, string $slug, ViewLeaderboardHandler $handler): JsonResponse
    {
        $limit = (int) $request->integer('limit', 10);
        $board = $handler->handle(new ViewLeaderboardQuery($slug, $this->optionalParticipant($request), $limit));

        return response()->json([
            'data' => [
                'entries' => array_map(static fn ($e): array => SnakeArray::from((array) $e), $board->entries),
                'me' => $board->me !== null ? SnakeArray::from((array) $board->me) : null,
            ],
        ]);
    }

    public function openSession(OpenSessionRequest $request, OpenSessionHandler $handler): JsonResponse
    {
        $result = $handler->handle(new OpenSessionCommand(
            channel: $request->string('channel')->toString(),
            campaignSlug: $request->string('campaign_slug')->toString(),
            channelToken: $request->string('channel_token')->toString(),
            displayName: $request->string('display_name')->toString(),
        ));

        return response()->json(['data' => SnakeArray::from((array) $result)], 201);
    }

    public function playAttempt(PlayAttemptRequest $request, string $slug, PlayAttemptHandler $handler): JsonResponse
    {
        $participantId = (string) $request->attributes->get(ResolveParticipant::ATTRIBUTE);
        $result = $handler->handle(new PlayAttemptCommand($slug, $participantId, $request->movePayload()));

        return response()->json(['data' => SnakeArray::from((array) $result)]);
    }

    public function myRewards(Request $request, string $slug, MyRewardsHandler $handler): JsonResponse
    {
        $participantId = (string) $request->attributes->get(ResolveParticipant::ATTRIBUTE);
        $items = $handler->handle(new MyRewardsQuery($slug, $participantId));

        return response()->json(['data' => ['items' => array_map(static fn ($r): array => SnakeArray::from((array) $r), $items)]]);
    }

    private function optionalParticipant(Request $request): ?string
    {
        $value = $request->attributes->get(ResolveParticipant::ATTRIBUTE);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
