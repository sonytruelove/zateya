<?php

declare(strict_types=1);

namespace Src\Presentation\Http\Api;

use Illuminate\Http\JsonResponse;
use Src\Application\Campaign\AddPrizes\AddPrizesCommand;
use Src\Application\Campaign\AddPrizes\AddPrizesHandler;
use Src\Application\Campaign\CampaignStats\CampaignStatsHandler;
use Src\Application\Campaign\CampaignStats\CampaignStatsQuery;
use Src\Application\Campaign\CreateCampaign\CreateCampaignCommand;
use Src\Application\Campaign\CreateCampaign\CreateCampaignHandler;
use Src\Application\Campaign\DeleteCampaign\DeleteCampaignCommand;
use Src\Application\Campaign\DeleteCampaign\DeleteCampaignHandler;
use Src\Application\Campaign\PublishCampaign\PublishCampaignCommand;
use Src\Application\Campaign\PublishCampaign\PublishCampaignHandler;
use Src\Domain\Campaign\Campaign;
use Src\Domain\Campaign\CampaignRepository;
use Src\Presentation\Http\Request\AddPrizesRequest;
use Src\Presentation\Http\Request\CreateCampaignRequest;
use Src\Presentation\Http\Request\UploadPromoCodesRequest;
use Src\Presentation\Http\SnakeArray;

final class AdminController
{
    public function listCampaigns(CampaignRepository $campaigns): JsonResponse
    {
        $items = array_map(
            static fn (Campaign $c): array => [
                'id' => (string) $c->id,
                'slug' => (string) $c->slug,
                'title' => $c->title(),
                'mechanic' => $c->mechanic->value,
                'mechanic_title' => $c->mechanic->title(),
                'status' => $c->status()->value,
                'status_title' => $c->status()->title(),
                'starts_at' => $c->period()->startsAt->format(DATE_ATOM),
                'ends_at' => $c->period()->endsAt->format(DATE_ATOM),
            ],
            $campaigns->all(),
        );

        return response()->json(['data' => ['items' => $items]]);
    }

    public function createCampaign(CreateCampaignRequest $request, CreateCampaignHandler $handler): JsonResponse
    {
        $result = $handler->handle(new CreateCampaignCommand(
            slug: $request->string('slug')->toString(),
            title: $request->string('title')->toString(),
            mechanic: $request->string('mechanic')->toString(),
            startsAt: $request->string('starts_at')->toString(),
            endsAt: $request->string('ends_at')->toString(),
            colorHex: $request->string('color_hex', '#0b57d0')->toString(),
            emoji: $request->string('emoji', '🎯')->toString(),
            attemptsPerParticipant: (int) $request->integer('attempts_per_participant', 3),
            mechanicSettings: $request->mechanicSettings(),
        ));

        return response()->json(['data' => SnakeArray::from((array) $result)], 201);
    }

    public function publishCampaign(string $id, PublishCampaignHandler $handler): JsonResponse
    {
        $handler->handle(new PublishCampaignCommand($id));

        return response()->json(['data' => ['status' => 'published']]);
    }

    public function addPrizes(AddPrizesRequest $request, string $id, AddPrizesHandler $handler): JsonResponse
    {
        $left = $handler->handle(new AddPrizesCommand(
            $id,
            $request->string('title')->toString(),
            (int) $request->integer('quantity'),
        ));

        return response()->json(['data' => ['prize_pool_left' => $left]], 201);
    }

    public function uploadPromoCodes(UploadPromoCodesRequest $request, string $id, \Src\Application\Campaign\UploadPromoCodes\UploadPromoCodesHandler $handler): JsonResponse
    {
        $result = $handler->handle(new \Src\Application\Campaign\UploadPromoCodes\UploadPromoCodesCommand($id, $request->codes()));

        return response()->json(['data' => $result], 201);
    }

    public function deleteCampaign(string $id, DeleteCampaignHandler $handler): JsonResponse
    {
        $handler->handle(new DeleteCampaignCommand($id));

        return response()->json(null, 204);
    }

    public function stats(string $id, CampaignStatsHandler $handler): JsonResponse
    {
        $stats = $handler->handle(new CampaignStatsQuery($id));

        return response()->json(['data' => SnakeArray::from((array) $stats)]);
    }
}
