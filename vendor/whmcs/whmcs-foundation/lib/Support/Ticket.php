<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Support;

class Ticket extends \WHMCS\Model\AbstractModel
{
    protected $table = "tbltickets";
    protected $columnMap = array("ticketNumber" => "tid", "departmentId" => "did", "subject" => "title", "flaggedAdminId" => "flag", "replyingAdminId" => "replyingadmin", "adminRead" => "adminunread", "priority" => "urgency", "createdByAdminUser" => "admin", "mergedWithTicketId" => "merged_ticket_id");
    protected $commaSeparated = array("adminunread");
    protected $dates = array("date", "lastreply", "replyingtime");
    protected $hidden = array("flag", "adminunread", "clientunread", "replyingadmin", "replyingtime", "editor");
    const CREATED_AT = "date";
    public static function boot()
    {
        parent::boot();
        static::addGlobalScope("ordered", function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->orderBy("tbltickets.lastreply");
        });
    }
    public function client()
    {
        return $this->belongsTo("WHMCS\\User\\Client", "userid");
    }
    public function contact()
    {
        return $this->belongsTo("WHMCS\\User\\Client\\Contact", "contactid");
    }
    public function department()
    {
        return $this->belongsTo("WHMCS\\Support\\Department", "did");
    }
    public function flaggedAdmin()
    {
        return $this->belongsTo("WHMCS\\User\\Admin", "flag");
    }
    public function replies()
    {
        return $this->hasMany("WHMCS\\Support\\Ticket\\Reply", "tid");
    }
    public function mergedTicket()
    {
        return $this->hasOne("WHMCS\\Support\\Ticket", "merged_ticket_id");
    }
    public function replyingAdmin()
    {
        return $this->belongsTo("WHMCS\\User\\Admin", "replyingadmin");
    }
    public function scopeUserId(\Illuminate\Database\Eloquent\Builder $query, $userId)
    {
        return $query->where("userid", $userId);
    }
    public function scopeNotMerged(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where("merged_ticket_id", 0);
    }
    public function mergeOtherTicketsInToThis(array $ticketIds)
    {
        $saveRequired = false;
        addTicketLog($this->id, "Merged Tickets " . implode(",", $ticketIds));
        getUsersLang($this->userId);
        $merge = \Lang::trans("ticketmerge");
        if (!$merge || $merge == "" || $merge == "ticketmerge") {
            $merge = "MERGED";
        }
        if (strpos($this->title, " [" . $merge . "]") === false) {
            $this->title = $this->title . " [" . $merge . "]";
            $saveRequired = true;
        }
        $ticketStatus = $this->status;
        $ticketLastReply = $this->lastReply;
        foreach ($ticketIds as $id) {
            if ($id != $this->id) {
                try {
                    $mergingTicketData = Ticket::findOrFail($id);
                    \WHMCS\Database\Capsule::table("tblticketlog")->where("tid", "=", $id)->update(array("tid" => $this->id));
                    \WHMCS\Database\Capsule::table("tblticketnotes")->where("ticketid", "=", $id)->update(array("ticketid" => $this->id));
                    $mergingTicketData->replies()->update(array("tid" => $this->id));
                    $newReply = new Ticket\Reply();
                    $newReply->tid = $this->id;
                    $newReply->clientId = $this->userId;
                    $newReply->name = $mergingTicketData->name;
                    $newReply->email = $mergingTicketData->email;
                    $newReply->date = $mergingTicketData->date;
                    $newReply->message = $mergingTicketData->message;
                    $newReply->admin = $mergingTicketData->admin;
                    $newReply->attachment = $mergingTicketData->attachment;
                    $newReply->editor = $mergingTicketData->editor;
                    $newReply->save();
                    if ($ticketLastReply < $mergingTicketData->lastReply) {
                        $ticketLastReply = $mergingTicketData->lastReply;
                        $ticketStatus = $mergingTicketData->status;
                    }
                    $mergingTicketData->mergedTicketId = $this->id;
                    $mergingTicketData->status = "Closed";
                    $mergingTicketData->message = "";
                    $mergingTicketData->admin = "";
                    $mergingTicketData->attachment = "";
                    $mergingTicketData->email = "";
                    $mergingTicketData->flaggedAdminId = 0;
                    $mergingTicketData->save();
                    $mergingTicketData->mergedTicket()->update(array("merged_ticket_id" => $this->id));
                    addTicketLog($mergingTicketData, "Ticket ID: " . $mergingTicketData->id . " Merged with Ticket ID: " . $this->id);
                } catch (\Exception $e) {
                }
            }
        }
        if ($this->lastReply < $ticketLastReply) {
            $this->lastReply = $ticketLastReply;
            $this->status = $ticketStatus;
            $saveRequired = true;
        }
        if ($saveRequired) {
            $this->save();
        }
    }
}

?>