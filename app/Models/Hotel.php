namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hotel extends Model
{
    protected $fillable = ['name', 'location', 'description', 'thumbnail'];

    // Relasi ke Kamar
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }
}